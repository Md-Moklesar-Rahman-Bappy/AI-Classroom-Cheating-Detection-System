<?php

namespace App\Console\Commands;

use App\Models\EventEvidence;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class EvidenceVerify extends Command
{
    protected $signature = 'evidence:verify {--job= : Filter by job id}';
    protected $description = 'Verify SHA256 integrity of evidence files';

    public function handle(): int
    {
        $q = EventEvidence::query();
        if ($this->option('job')) {
            $q->whereHas('event', fn($qq) => $qq->where('analysis_job_id', $this->option('job')));
        }
        $count = 0; $verified = 0; $failed = 0; $missing = 0; $legacy = 0;
        foreach ($q->cursor() as $ev) {
            $count++;
            if (! $ev->checksum_sha256) {
                $legacy++;
                $this->line("{$ev->id}: Not yet verified (legacy)");
                continue;
            }
            if (! Storage::disk('local')->exists($ev->file_path)) {
                $missing++;
                $this->warn("{$ev->id}: File unavailable {$ev->file_path}");
                continue;
            }
            $actual = hash_file('sha256', Storage::disk('local')->path($ev->file_path));
            if (strtolower($actual) === strtolower($ev->checksum_sha256)) {
                $verified++;
            } else {
                $failed++;
                $this->error("{$ev->id}: Verification failed expected ".substr($ev->checksum_sha256,0,12)." got ".substr($actual,0,12));
            }
        }
        $this->info("Verified: {$verified}/{$count} | Failed: {$failed} | Missing: {$missing} | Legacy: {$legacy}");
        $this->info("Hash verification proves stored bytes match recorded digest, not that detection is correct.");
        return $failed > 0 ? 1 : 0;
    }
}
