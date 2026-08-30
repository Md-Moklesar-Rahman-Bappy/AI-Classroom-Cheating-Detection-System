<?php

namespace App\Http\Controllers;

use App\Helpers\AuditHelper;
use App\Models\AnalysisJob;
use App\Models\CameraSource;
use App\Models\ExamSession;
use App\Models\ModelVersion;
use App\Services\AiServiceClient;
use App\Services\AiServiceException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class LiveController extends Controller
{
    public function index(Request $request)
    {
        $sessions = ExamSession::with('room')->latest()->get();
        $cameras = CameraSource::with('session')->latest()->get();

        return view('live.index', compact('sessions', 'cameras'));
    }

    public function start(Request $request, AiServiceClient $client)
    {
        $request->validate([
            'exam_session_id' => 'required|exists:exam_sessions,id',
            'camera_source_id' => 'nullable|exists:camera_sources,id',
            'source_type' => 'required|in:webcam,rtsp,http,test,test_source',
            'identifier' => 'required|string|max:500',
        ]);
        $user = auth()->user();
        if (! $user->hasAnyRole(['system_admin', 'exam_admin', 'invigilator'])) {
            abort(403, 'Forbidden - insufficient role');
        }

        // Prevent duplicate start for same session
        $existing = AnalysisJob::where('exam_session_id', $request->exam_session_id)->whereIn('status', ['processing', 'queued'])->where('source_type', 'live_stream')->first();
        if ($existing) {
            return back()->withErrors(['live' => 'Already monitoring session '.$existing->exam_session_id.' (job '.$existing->id.' is '.$existing->status.')']);
        }

        $correlationId = (string) Str::uuid();
        try {
            $payload = [
                'source_type' => $request->source_type,
                'identifier' => $request->identifier,
                'session_name' => 'live-'.$request->exam_session_id,
            ];
            $result = $client->createLiveSession($payload, $correlationId);
            $sessionId = $result['session_id'] ?? (string) Str::uuid();
            // Create local job record for live session
            $job = AnalysisJob::create([
                'exam_session_id' => $request->exam_session_id,
                'source_type' => 'live_stream',
                'camera_source_id' => $request->camera_source_id,
                'model_version_id' => ModelVersion::first()?->id ?? 1,
                'status' => 'processing',
                'remote_job_id' => $sessionId,
                'remote_status' => 'monitoring',
                'correlation_id' => $correlationId,
                'config' => ['source_type' => $request->source_type, 'identifier' => $request->identifier],
                'created_by' => auth()->id(),
                'started_at' => now(),
            ]);
            AuditHelper::log('live_start', 'exam_session', (string) $request->exam_session_id, 'success', ['session_id' => $sessionId, 'correlation_id' => $correlationId]);

            return redirect()->route('live.show', $sessionId)->with('success', 'Monitoring started (session '.$sessionId.')');
        } catch (AiServiceException $e) {
            if ($e->statusCode === 409) {
                return back()->withErrors(['live' => 'Single-source limit: already monitoring'])->withInput();
            }

            return back()->withErrors(['live' => 'AI service error: '.substr($e->getMessage(), 0, 200)])->withInput();
        } catch (\Throwable $e) {
            return back()->withErrors(['live' => 'Failed to start monitoring: '.substr($e->getMessage(), 0, 200)])->withInput();
        }
    }

    public function show(Request $request, string $sessionId)
    {
        $user = auth()->user();
        if (! $user->hasAnyRole(['system_admin', 'exam_admin', 'invigilator', 'reviewer', 'auditor'])) {
            abort(403);
        }
        try {
            Str::uuid($sessionId);
        } catch (\Throwable $e) {
            abort(422, 'Invalid session_id');
        }
        $examSession = ExamSession::with('room')->first();

        return view('live.show', compact('sessionId', 'examSession'));
    }

    public function stop(Request $request, string $sessionId, AiServiceClient $client)
    {
        $user = auth()->user();
        if (! $user->hasAnyRole(['system_admin', 'exam_admin', 'invigilator'])) {
            abort(403);
        }
        try {
            Str::uuid($sessionId);
        } catch (\Throwable $e) {
            abort(422);
        }
        $correlationId = (string) Str::uuid();
        try {
            $client->stopLiveSession($sessionId, $correlationId);
        } catch (\Throwable $e) {
            // Idempotent: if already stopped, still mark local as stopped
            Log::warning('Live stop failed, still marking stopped', ['session_id' => $sessionId, 'error' => $e->getMessage()]);
        }
        // Update local job if exists
        $job = AnalysisJob::where('remote_job_id', $sessionId)->first();
        if ($job) {
            $job->update(['status' => 'cancelled', 'remote_status' => 'stopped']);
        }
        AuditHelper::log('live_stop', 'exam_session', $sessionId, 'success', ['correlation_id' => $correlationId]);

        return redirect()->route('live.index')->with('success', 'Monitoring stopped');
    }

    public function health(Request $request, string $sessionId, AiServiceClient $client)
    {
        $user = auth()->user();
        if (! $user->hasAnyRole(['system_admin', 'exam_admin', 'invigilator', 'reviewer', 'auditor'])) {
            abort(403);
        }
        try {
            $data = $client->getLiveHealth($sessionId);

            return response()->json($data);
        } catch (\Throwable $e) {
            return response()->json(['error' => substr($e->getMessage(), 0, 200)], 503);
        }
    }

    public function events(Request $request, string $sessionId, AiServiceClient $client)
    {
        $user = auth()->user();
        if (! $user->hasAnyRole(['system_admin', 'exam_admin', 'invigilator', 'reviewer', 'auditor'])) {
            abort(403);
        }
        try {
            $data = $client->getLiveEvents($sessionId);

            return response()->json($data);
        } catch (\Throwable $e) {
            return response()->json(['error' => substr($e->getMessage(), 0, 200)], 503);
        }
    }

    public function preview(Request $request, string $sessionId, AiServiceClient $client)
    {
        $user = auth()->user();
        if (! $user->hasAnyRole(['system_admin', 'exam_admin', 'invigilator', 'reviewer', 'auditor'])) {
            abort(403);
        }
        // Proxy MJPEG from AI service with auth, never expose credentials
        try {
            $response = $client->proxyLivePreview($sessionId);

            return $response;
        } catch (\Throwable $e) {
            abort(503, 'Preview unavailable');
        }
    }
}
