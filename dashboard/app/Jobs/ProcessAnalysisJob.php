<?php

namespace App\Jobs;

use App\Helpers\AuditHelper;
use App\Models\AnalysisJob;
use App\Models\DetectionEvent;
use App\Models\EventEvidence;
use App\Models\ProcessingMetric;
use App\Services\AiServiceClient;
use App\Services\AiServiceException;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProcessAnalysisJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 1;

    public $timeout = 600;

    public function __construct(public int $analysisJobId, public string $correlationId = '') {}

    public function handle(AiServiceClient $client): void
    {
        $job = AnalysisJob::find($this->analysisJobId);
        if (! $job) {
            return;
        }
        if (! in_array($job->status, ['pending', 'queued'])) {
            return;
        }
        $correlationId = $this->correlationId ?: (string) Str::uuid();
        $job->update(['correlation_id' => $correlationId, 'status' => 'queued']);
        try {
            $videoAsset = $job->videoAsset;
            if (! $videoAsset) {
                $job->update(['status' => 'failed', 'failure_reason' => 'Video asset not found', 'failed_at' => now()]);

                return;
            }
            $filePath = Storage::disk('local')->path('video_assets/'.$videoAsset->stored_filename);
            if (! file_exists($filePath)) {
                $job->update(['status' => 'failed', 'failure_reason' => 'Video file missing', 'failed_at' => now()]);

                return;
            }
            $job->update(['status' => 'processing', 'started_at' => now(), 'progress_percent' => 5]);
            // Prevent duplicate submission via correlation_id
            if ($job->remote_job_id) {
                Log::info('Duplicate submission prevented', ['job_id' => $job->id, 'remote_job_id' => $job->remote_job_id]);

                return;
            }
            $result = $client->createRecordedJob($filePath, $videoAsset->original_filename, $correlationId);
            $remoteId = $result['job_id'] ?? null;
            if (! $remoteId) {
                throw new \RuntimeException('No remote job ID returned');
            }
            $job->update(['remote_job_id' => $remoteId, 'remote_status' => $result['status'] ?? 'processing', 'progress_percent' => $result['progress_percent'] ?? 10, 'correlation_id' => $correlationId]);
            // Poll for completion (AI service processes synchronously, but we poll to sync)
            $attempts = 0;
            while ($attempts < 30) {
                sleep(2);
                $attempts++;
                try {
                    $remote = $client->getJob($remoteId, $correlationId);
                    $status = $remote['status'] ?? 'processing';
                    $progress = $remote['progress_percent'] ?? $job->progress_percent;
                    $job->update(['remote_status' => $status, 'remote_progress' => $progress, 'progress_percent' => min(95, $progress)]);
                    if (in_array($status, ['completed', 'failed', 'cancelled'])) {
                        break;
                    }
                } catch (\Throwable $e) {
                    Log::warning('Poll failed', ['job_id' => $job->id, 'error' => $e->getMessage()]);
                    if ($attempts >= 5) {
                        throw $e;
                    }
                }
            }
            $final = $client->getJob($remoteId, $correlationId);
            $finalStatus = $final['status'] ?? 'failed';
            if ($finalStatus === 'cancelled') {
                $job->update(['status' => 'cancelled', 'progress_percent' => 100, 'completed_at' => now()]);

                return;
            }
            if ($finalStatus === 'failed') {
                $job->update(['status' => 'failed', 'failure_reason' => $final['failure_reason'] ?? 'AI processing failed', 'failed_at' => now(), 'progress_percent' => 100]);

                return;
            }
            // Success: sync events, metrics, evidence
            $eventsData = $client->getEvents($remoteId, $correlationId);
            $metricsData = $client->getMetrics($remoteId, $correlationId);
            $imported = 0;
            foreach ($eventsData['data'] ?? [] as $ev) {
                $eventType = $ev['event_type'] ?? 'D2';
                $typeMap = ['Mobile Phone Detected' => 'D2', 'Repeated Looking Left' => 'B1', 'Repeated Looking Right' => 'B2', 'Looking Backward' => 'B3', 'Leaving Seat' => 'B4'];
                $mapped = $typeMap[$eventType] ?? (in_array($eventType, ['D1', 'D2', 'B1', 'B2', 'B3', 'B4']) ? $eventType : 'B1');
                // idempotent sync via event_id
                $exists = DetectionEvent::where('id', $ev['event_id'] ?? null)->exists();
                if (isset($ev['event_id']) && $exists) {
                    continue;
                }
                // Also check duplicate by job + track + type + start_frame
                $dup = DetectionEvent::where('analysis_job_id', $job->id)->where('event_type', $mapped)->where('temporary_track_id', $ev['track_id'] ?? $ev['frame_number'] ?? 0)->where('started_at_frame', $ev['frame_number'] ?? $ev['start_frame'] ?? 0)->exists();
                if ($dup) {
                    continue;
                }
                $detection = DetectionEvent::create([
                    'exam_session_id' => $job->exam_session_id,
                    'analysis_job_id' => $job->id,
                    'model_version_id' => $job->model_version_id,
                    'source_type' => 'recorded_video',
                    'temporary_track_id' => $ev['track_id'] ?? 1,
                    'event_type' => $mapped,
                    'event_status' => 'active',
                    'started_at_frame' => $ev['frame_number'] ?? $ev['start_frame'] ?? null,
                    'ended_at_frame' => $ev['frame_number'] ?? $ev['end_frame'] ?? null,
                    'started_at_seconds' => $ev['timestamp_seconds'] ?? $ev['start_time'] ?? null,
                    'ended_at_seconds' => $ev['timestamp_seconds'] ?? $ev['end_time'] ?? null,
                    'confidence' => $ev['confidence'] ?? null,
                    'rule_score' => $ev['confidence'] ?? null,
                    'evidence_available' => false,
                    'review_status' => 'pending',
                ]);
                $imported++;
                // Evidence: try to copy from AI service storage if exists
                $this->copyEvidence($job, $detection, $ev, $correlationId);
            }
            // Save metrics
            $metrics = $metricsData['metrics'] ?? [];
            if (! empty($metrics)) {
                ProcessingMetric::updateOrCreate(['analysis_job_id' => $job->id], [
                    'source_fps' => $metrics['source_fps'] ?? null,
                    'processing_fps' => $metrics['effective_processing_fps'] ?? $metrics['processing_fps'] ?? null,
                    'detection_latency_ms' => $metrics['avg_detection_latency_ms'] ?? null,
                    'cpu_percent' => $metrics['peak_memory_mb'] ?? null,
                    'memory_mb' => $metrics['peak_memory_mb'] ?? null,
                    'dropped_frames' => $metrics['skipped_frame_count'] ?? 0,
                    'job_duration_seconds' => $metrics['processing_duration_seconds'] ?? null,
                ]);
            }
            $job->update([
                'status' => 'completed',
                'progress_percent' => 100,
                'completed_at' => now(),
                'remote_status' => 'completed',
                'remote_output_metadata' => $final['output_metadata'] ?? null,
                'failure_reason' => null,
            ]);
            AuditHelper::log('job_completed', 'analysis_job', (string) $job->id, 'success', ['remote_job_id' => $remoteId]);
        } catch (AiServiceException $e) {
            $job->update(['status' => 'failed', 'failure_reason' => $this->sanitizeError($e->getMessage()), 'failed_at' => now()]);
            AuditHelper::log('job_failed', 'analysis_job', (string) $job->id, 'failure', ['error' => $this->sanitizeError($e->getMessage())]);
            Log::error('ProcessAnalysisJob failed', ['job_id' => $job->id, 'error' => $e->getMessage(), 'status' => $e->statusCode]);
        } catch (\Throwable $e) {
            $job->update(['status' => 'failed', 'failure_reason' => $this->sanitizeError($e->getMessage()), 'failed_at' => now()]);
            Log::error('ProcessAnalysisJob exception', ['job_id' => $job->id, 'error' => $e->getMessage()]);
        }
    }

    private function copyEvidence(AnalysisJob $job, DetectionEvent $event, array $evData, string $correlationId): void
    {
        try {
            // Try to locate evidence in AI service filesystem (if shared)
            $aiEvidenceBase = base_path('../ai-service/evidence');
            $remoteJobId = $job->remote_job_id;
            if ($remoteJobId && is_dir($aiEvidenceBase.'/'.$remoteJobId)) {
                $files = glob($aiEvidenceBase.'/'.$remoteJobId.'/*.jpg');
                if (! empty($files)) {
                    $src = $files[0];
                    $destDir = 'evidence/'.$job->id;
                    $destFilename = $event->id.'_'.basename($src);
                    $destPath = $destDir.'/'.$destFilename;
                    Storage::disk('local')->makeDirectory($destDir);
                    Storage::disk('local')->put($destPath, file_get_contents($src));
                    EventEvidence::create([
                        'detection_event_id' => $event->id,
                        'file_path' => $destPath,
                        'file_type' => 'snapshot',
                        'frame_number' => $evData['frame_number'] ?? $evData['start_frame'] ?? null,
                        'captured_at_seconds' => $evData['timestamp_seconds'] ?? $evData['start_time'] ?? null,
                        'width' => null, 'height' => null,
                        'checksum_sha256' => hash_file('sha256', Storage::disk('local')->path($destPath)),
                    ]);
                    $event->update(['evidence_available' => true]);

                    return;
                }
            }
            // Fallback: create placeholder evidence record without file if not found
            // Do not expose absolute paths
        } catch (\Throwable $e) {
            Log::warning('Evidence copy failed', ['event_id' => $event->id, 'error' => $e->getMessage()]);
        }
    }

    private function sanitizeError(string $msg): string
    {
        // Remove secrets, limit length, no stack trace
        $msg = preg_replace("/(token|password|secret|key)=[^&\s]+/i", '$1=[REDACTED]', $msg) ?? $msg;

        return substr($msg,0,500);
    }
}
