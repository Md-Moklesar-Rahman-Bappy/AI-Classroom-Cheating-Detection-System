<?php

namespace App\Http\Controllers;

use App\Helpers\AuditHelper;
use App\Models\ExamSession;
use App\Models\VideoAsset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class VideoAssetController extends Controller
{
    public function index()
    {
        $queries = [];
        \Illuminate\Support\Facades\DB::listen(function ($query) use (&$queries) {
            $queries[] = [
                'sql' => $query->sql,
                'bindings' => $query->bindings,
                'time_ms' => $query->time,
                'table_hint' => (str_contains($query->sql, 'video_assets') ? 'video_assets' : 'other'),
            ];
        });

        \Illuminate\Support\Facades\Log::info('VideoAsset page index started', [
            'table' => (new VideoAsset)->getTable(),
            'connection' => \Illuminate\Support\Facades\DB::connection()->getDatabaseName(),
            'soft_deletes_trait' => in_array(\Illuminate\Database\Eloquent\SoftDeletes::class, class_uses(VideoAsset::class)),
        ]);
        $assets = VideoAsset::with('session')->latest()->paginate(10);
        \Illuminate\Support\Facades\Log::info('VideoAsset page index completed', [
            'count' => $assets->count(),
            'queries_executed' => count($queries),
            'queries' => $queries,
        ]);

        return view('video-assets.index', compact('assets'));
    }

    public function create()
    {
        $sessions = ExamSession::all();

        return view('video-assets.create', compact('sessions'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'exam_session_id' => 'required|exists:exam_sessions,id',
            'video' => 'required|file|mimes:mp4,avi,mov,mkv|max:512000',
        ]);
        $file = $request->file('video');
        $mime = $file->getMimeType();
        $allowedMimes = ['video/mp4', 'video/avi', 'video/quicktime', 'video/x-msvideo', 'video/x-matroska'];
        if (! in_array($mime, $allowedMimes) && ! str_starts_with($mime, 'video/')) {
            return back()->withErrors(['video' => 'Invalid video MIME: '.$mime])->withInput();
        }
        $stored = Str::uuid().'.'.$file->getClientOriginalExtension();
        $path = $file->storeAs('video_assets', $stored, 'local');
        if (! $path) {
            return back()->withErrors(['video' => 'Failed to store file'])->withInput();
        }
        // Validate not empty - use file getSize for fake files
        if ($file->getSize() === 0) {
            Storage::disk('local')->delete($path);

            return back()->withErrors(['video' => 'Empty file'])->withInput();
        }
        $asset = VideoAsset::create([
            'exam_session_id' => $request->exam_session_id,
            'original_filename' => $file->getClientOriginalName(),
            'stored_filename' => $stored,
            'mime_type' => $mime,
            'size_bytes' => $file->getSize(),
            'validation_status' => 'valid',
            'uploaded_by' => auth()->id(),
        ]);
        AuditHelper::log('video_uploaded', 'video_asset', (string) $asset->id, 'success', ['original' => $asset->original_filename, 'size' => $asset->size_bytes]);
        $this->cleanAbandoned();

        return redirect()->route('video-assets.index')->with('success', 'Video uploaded (ID '.$asset->id.')');
    }

    public function show(VideoAsset $videoAsset)
    {
        return view('video-assets.show', compact('videoAsset'));
    }

    public function edit(VideoAsset $videoAsset)
    {
        $sessions = ExamSession::all();

        return view('video-assets.edit', compact('videoAsset', 'sessions'));
    }

    public function update(Request $request, VideoAsset $videoAsset)
    {
        $request->validate([
            'exam_session_id' => 'required|exists:exam_sessions,id',
            'original_filename' => 'nullable|string|max:255',
            'validation_status' => 'nullable|in:pending,valid,invalid',
        ]);
        $videoAsset->update([
            'exam_session_id' => $request->exam_session_id,
            'original_filename' => $request->original_filename ?? $videoAsset->original_filename,
            'validation_status' => $request->validation_status ?? $videoAsset->validation_status,
        ]);
        AuditHelper::log('video_updated', 'video_asset', (string) $videoAsset->id, 'success');

        return redirect()->route('video-assets.index')->with('success', 'Updated');
    }

    public function destroy(VideoAsset $videoAsset)
    {
        if ($videoAsset->analysisJobs()->exists()) {
            AuditHelper::log('video_delete_blocked', 'video_asset', (string) $videoAsset->id, 'failure', [
                'reason' => 'linked_jobs_exist',
                'linked_count' => $videoAsset->analysisJobs()->count(),
            ]);

            return back()->withErrors(['video' => 'Cannot delete video with linked jobs (count: '.$videoAsset->analysisJobs()->count().')']);
        }
        $videoAsset->delete();
        AuditHelper::log('video_deleted', 'video_asset', (string) $videoAsset->id, 'success', [
            'filename' => $videoAsset->original_filename,
        ]);

        return redirect()->route('video-assets.index')->with('success', 'Deleted (soft deleted, recoverable)');
    }

    private function cleanAbandoned(): void
    {
        try {
            $files = Storage::disk('local')->files('video_assets');
            foreach ($files as $f) {
                $path = Storage::disk('local')->path($f);
                if (file_exists($path) && time() - filemtime($path) > 3600) {
                    $basename = basename($f);
                    if (! VideoAsset::where('stored_filename', $basename)->exists()) {
                        Storage::disk('local')->delete($f);
                    }
                }
            }
        } catch (\Throwable $e) {
        }
    }
}
