<?php

namespace App\Http\Controllers;

use App\Helpers\AuditHelper;
use App\Jobs\ProcessAnalysisJob;
use App\Jobs\SyncAnalysisJob;
use App\Models\AnalysisJob;
use App\Models\ExamSession;
use App\Models\ModelVersion;
use App\Models\VideoAsset;
use App\Services\AiServiceClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AnalysisJobController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', AnalysisJob::class);
        $jobs = AnalysisJob::with(['session', 'modelVersion'])->latest()->paginate(10);

        return view('analysis-jobs.index', compact('jobs'));
    }

    public function create()
    {
        $this->authorize('create', AnalysisJob::class);
        $sessions = ExamSession::all();
        $models = ModelVersion::active()->get();
        $videoAssets = VideoAsset::with('session')->latest()->get();

        return view('analysis-jobs.create', compact('sessions', 'models', 'videoAssets'));
    }

    public function edit(AnalysisJob $analysisJob)
    {
        $this->authorize('update', $analysisJob);
        $sessions = ExamSession::all();
        $models = ModelVersion::active()->get();
        $videoAssets = VideoAsset::with('session')->latest()->get();

        return view('analysis-jobs.edit', compact('analysisJob', 'sessions', 'models', 'videoAssets'));
    }

    public function update(Request $request, AnalysisJob $analysisJob)
    {
        $this->authorize('update', $analysisJob);
        $request->validate([
            'exam_session_id' => 'required|exists:exam_sessions,id',
            'source_type' => 'required|in:recorded_video,live_stream,webcam,test_source',
            'model_version_id' => 'required|exists:model_versions,id',
            'video_asset_id' => 'required_if:source_type,recorded_video|nullable|exists:video_assets,id',
        ]);
        $analysisJob->update($request->only(['exam_session_id', 'source_type', 'model_version_id', 'video_asset_id']));
        AuditHelper::log('job_edited', 'analysis_job', (string) $analysisJob->id, 'success', ['changes' => $request->only(['exam_session_id', 'source_type'])]);
        Log::info('Analysis job edited', ['job_id' => $analysisJob->id, 'user_id' => auth()->id()]);

        return redirect()->route('analysis-jobs.show', $analysisJob)->with('success', 'Job updated');
    }

    public function store(Request $request, AiServiceClient $client)
    {
        $this->authorize('create', AnalysisJob::class);
        $request->validate([
            'exam_session_id' => 'required|exists:exam_sessions,id',
            'source_type' => 'required|in:recorded_video,live_stream,webcam,test_source',
            'model_version_id' => 'required|exists:model_versions,id',
            'video_asset_id' => 'required_if:source_type,recorded_video|nullable|exists:video_assets,id',
        ]);
        // Prevent duplicate submission: same session + video + model within 5 minutes
        $recent = AnalysisJob::where('exam_session_id', $request->exam_session_id)
            ->where('video_asset_id', $request->video_asset_id)
            ->where('model_version_id', $request->model_version_id)
            ->where('created_at', '>', now()->subMinutes(5))
            ->whereIn('status', ['pending', 'queued', 'processing'])
            ->first();
        if ($recent) {
            return back()->withErrors(['video_asset_id' => 'Duplicate job submission detected (job '.$recent->id.' is '.$recent->status.')'])->withInput();
        }
        $job = AnalysisJob::create([
            'exam_session_id' => $request->exam_session_id,
            'source_type' => $request->source_type,
            'video_asset_id' => $request->video_asset_id,
            'model_version_id' => $request->model_version_id,
            'status' => 'pending',
            'config' => ['width' => 640, 'height' => 360, 'process_every_n_frames' => 3, 'confidence' => 0.25],
            'progress_percent' => 0,
            'correlation_id' => (string) Str::uuid(),
            'created_by' => auth()->id(),
        ]);
        AuditHelper::log('job_created', 'analysis_job', (string) $job->id, 'success', ['source_type' => $job->source_type]);
        // Dispatch async processing - do not block controller
        ProcessAnalysisJob::dispatch($job->id, $job->correlation_id);

        return redirect()->route('analysis-jobs.show', $job)->with('success', 'Job queued (ID '.$job->id.'). Processing in background. Refresh to see progress.');
    }

    public function show(AnalysisJob $analysisJob)
    {
        $this->authorize('view', $analysisJob);
        $analysisJob->load(['session', 'videoAsset', 'modelVersion', 'events.evidences', 'metrics']);
        // Auto-sync if remote job exists and not completed
        if ($analysisJob->remote_job_id && ! in_array($analysisJob->status, ['completed', 'failed', 'cancelled'])) {
            try {
                $client = app(AiServiceClient::class);
                $remote = $client->getJob($analysisJob->remote_job_id, $analysisJob->correlation_id);
                $analysisJob->update(['remote_status' => $remote['status'] ?? null, 'remote_progress' => $remote['progress_percent'] ?? null]);
                if (isset($remote['progress_percent']) && $remote['progress_percent'] != $analysisJob->progress_percent) {
                    $analysisJob->update(['progress_percent' => $remote['progress_percent']]);
                }
            } catch (\Throwable $e) {
            }
        }

        return view('analysis-jobs.show', compact('analysisJob'));
    }

    public function sync(AnalysisJob $analysisJob, AiServiceClient $client)
    {
        $this->authorize('view', $analysisJob);
        if (! $analysisJob->remote_job_id) {
            return back()->withErrors(['job' => 'No remote job to sync']);
        }
        try {
            $remote = $client->getJob($analysisJob->remote_job_id, $analysisJob->correlation_id);
            $analysisJob->update(['remote_status' => $remote['status'] ?? null, 'progress_percent' => $remote['progress_percent'] ?? $analysisJob->progress_percent]);
            // If completed, import events if not already
            if (($remote['status'] ?? '') === 'completed' && $analysisJob->events()->count() === 0) {
                $eventsData = $client->getEvents($analysisJob->remote_job_id, $analysisJob->correlation_id);
                // Import will be handled by ProcessAnalysisJob on next poll, but we can do quick sync
                SyncAnalysisJob::dispatch($analysisJob->id);
            }

            return back()->with('success', 'Synced: '.$remote['status']);
        } catch (\Throwable $e) {
            return back()->withErrors(['sync' => $e->getMessage()]);
        }
    }

    public function cancel(AnalysisJob $analysisJob, AiServiceClient $client)
    {
        $this->authorize('cancel', $analysisJob);
        if (in_array($analysisJob->status, ['completed', 'failed', 'cancelled'])) {
            return back()->withErrors(['job' => 'Cannot cancel '.$analysisJob->status]);
        }
        try {
            if ($analysisJob->remote_job_id) {
                try {
                    $client->cancelJob($analysisJob->remote_job_id, $analysisJob->correlation_id);
                } catch (\Throwable $e) {
                    Log::warning('Remote cancel failed, still cancelling locally', ['job_id' => $analysisJob->id, 'error' => $e->getMessage()]);
                }
            }
            $analysisJob->update(['status' => 'cancelled', 'progress_percent' => 100]);
            AuditHelper::log('job_cancelled', 'analysis_job', (string) $analysisJob->id);

            return back()->with('success', 'Job cancelled');
        } catch (\Throwable $e) {
            return back()->withErrors(['cancel' => $e->getMessage()]);
        }
    }

    public function retry(AnalysisJob $analysisJob)
    {
        $this->authorize('retry', $analysisJob);
        if (! in_array($analysisJob->status, ['failed', 'cancelled'])) {
            return back()->withErrors(['job' => 'Can only retry failed/cancelled']);
        }
        $newJob = AnalysisJob::create([
            'exam_session_id' => $analysisJob->exam_session_id,
            'source_type' => $analysisJob->source_type,
            'video_asset_id' => $analysisJob->video_asset_id,
            'model_version_id' => $analysisJob->model_version_id,
            'status' => 'pending',
            'config' => $analysisJob->config,
            'correlation_id' => (string) Str::uuid(),
            'created_by' => auth()->id(),
        ]);
        AuditHelper::log('job_retry', 'analysis_job', (string) $newJob->id, 'success', ['from_job' => $analysisJob->id]);
        ProcessAnalysisJob::dispatch($newJob->id, $newJob->correlation_id);

        return redirect()->route('analysis-jobs.show', $newJob)->with('success', 'Retry queued as job '.$newJob->id);
    }

    public function destroy(AnalysisJob $analysisJob)
    {
        $this->authorize('delete', $analysisJob);
        $analysisJob->delete();
        AuditHelper::log('job_deleted', 'analysis_job', (string) $analysisJob->id, 'success', ['soft_deleted' => true]);
        Log::info('Analysis job soft deleted', ['job_id' => $analysisJob->id, 'user_id' => auth()->id()]);

        return redirect()->route('analysis-jobs.index')->with('success', 'Job deleted (soft)');
    }
}
