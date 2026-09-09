<?php

namespace App\Console\Commands;

use App\Models\DetectionEvent;
use App\Models\EventEvidence;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class EvidenceCleanup extends Command
{
    protected $signature = 'evidence:cleanup {--dry-run : Preview without deleting} {--execute : Actually delete} {--retention-days=90 : Days to retain}';
    protected $description = 'Clean up expired evidence (dry-run by default)';

    public function handle(): int
    {
        $dry = $this->option('dry-run') || ! $this->option('execute');
        $days = (int) $this->option('retention-days');
        if ($dry) {
            $this->info("DRY RUN: would delete evidence older than {$days} days, protected evidence preserved");
        }
        $cutoff = now()->subDays($days);
        $query = EventEvidence::where('created_at', '<', $cutoff)
            ->whereHas('event', function ($q) {
                $q->whereNotIn('review_status', ['pending'])
                  ->where('review_status', '!=', 'needs_further_review');
            });
        $candidates = $query->with('event')->get();
        $eligible = $candidates->filter(function ($ev) {
            if (! $ev->event) return false;
            if (in_array($ev->event->review_status, ['pending', 'needs_further_review'])) return false;
            if ($ev->event->review_status === 'confirmed_suspicious') return false;
            if ($ev->deleted_at) return false;
            return true;
        });
        $grouped = $eligible->groupBy('detection_event_id');
        $toDelete = collect();
        foreach ($grouped as $eventId => $files) {
            $event = $files->first()->event;
            if ($event->review_status === 'confirmed_suspicious') continue;
            if ($files->count() == 2) {
                $hasTrigger = $files->contains(fn($f) => ($f->render_mode ?? 'trigger') === 'trigger');
                $hasLast = $files->contains(fn($f) => $f->render_mode === 'last_detected');
                if ($hasTrigger && $hasLast) {
                    $toDelete = $toDelete->merge($files);
                } else {
                    $toDelete = $toDelete->merge($files);
                }
            } else {
                $toDelete = $toDelete->merge($files);
            }
        }
        $this->info("Found {$toDelete->count()} evidence files eligible for cleanup");
        foreach ($toDelete as $ev) {
            $path = $ev->file_path;
            if (str_contains($path, '..') || str_contains($path, "\0")) {
                $this->warn("Rejected path traversal: {$path}");
                Log::warning('evidence cleanup rejected path', ['path' => $path]);
                continue;
            }
            $root = Storage::disk('local')->path('');
            $full = Storage::disk('local')->path($path);
            $realRoot = realpath($root) ?: $root;
            $realFull = realpath($full) ?: $full;
            if (! str_starts_with($realFull, $realRoot)) {
                $this->warn("Rejected outside evidence root: {$path}");
                Log::warning('evidence cleanup rejected outside root', ['path' => $path]);
                continue;
            }
            if (is_link($full)) {
                $this->warn("Rejected symlink: {$path}");
                continue;
            }
            if ($dry) {
                $this->line("[dry-run] would delete {$path} (event {$ev->detection_event_id})");
            } else {
                if (! Storage::disk('local')->exists($path)) {
                    $this->warn("Missing file reported: {$path}");
                    Log::warning('evidence cleanup missing file', ['path' => $path, 'id' => $ev->id]);
                    continue;
                }
                try {
                    Storage::disk('local')->delete($path);
                    $ev->delete();
                    Log::info('evidence cleanup deleted', ['id' => $ev->id, 'path' => $path]);
                    $this->line("Deleted {$path}");
                } catch (\Throwable $e) {
                    $this->error("Failed {$path}: ".$e->getMessage());
                    Log::error('evidence cleanup failed', ['path' => $path, 'error' => $e->getMessage()]);
                }
            }
        }
        if ($dry) {
            $this->info("Dry run complete — no files deleted. Use --execute to delete.");
        } else {
            $this->info("Cleanup executed: {$toDelete->count()} files processed");
        }
        return 0;
    }
}
