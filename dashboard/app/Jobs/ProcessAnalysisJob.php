<?php

namespace App\Jobs;

use App\Helpers\AuditHelper;
use App\Models\AnalysisJob;
use App\Models\DetectionEvent;
use App\Models\EventEvidence;
use App\Models\ProcessingMetric;
use App\Models\VideoAsset;
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
            Log::info('ProcessAnalysisJob lookup', [
                'job_id' => $job->id,
                'video_asset_id' => $job->video_asset_id,
                'video_asset_relation_loaded' => $job->relationLoaded('videoAsset') ? 'yes' : 'no',
                'direct_lookup' => VideoAsset::find($job->video_asset_id) ? 'found' : 'not found',
            ]);
            $videoAsset = $job->videoAsset;
            Log::info('ProcessAnalysisJob videoAsset', [
                'job_id' => $job->id,
                'video_asset_id' => $job->video_asset_id,
                'found' => $videoAsset ? 'yes' : 'no',
                'stored_filename' => $videoAsset?->stored_filename ?? 'null',
                'lookup_disk' => 'local',
                'lookup_path' => $videoAsset ? 'video_assets/'.$videoAsset->stored_filename : 'null',
                'storage_exists' => $videoAsset ? (Storage::disk('local')->exists('video_assets/'.$videoAsset->stored_filename) ? 'true' : 'false') : 'n/a',
                'absolute_path' => $videoAsset ? Storage::disk('local')->path('video_assets/'.$videoAsset->stored_filename) : 'null',
            ]);
            if (! $videoAsset) {
                Log::warning('ProcessAnalysisJob failed: Video asset not found', [
                    'job_id' => $job->id,
                    'video_asset_id' => $job->video_asset_id,
                    'all_video_assets_count' => VideoAsset::count(),
                    'all_video_asset_ids' => VideoAsset::pluck('id')->toArray(),
                ]);
                $job->update(['status' => 'failed', 'failure_reason' => 'Video asset not found (id='.$job->video_asset_id.')', 'failed_at' => now()]);

                return;
            }
            $lookupDisk = 'local';
            $lookupPath = 'video_assets/'.$videoAsset->stored_filename;
            $storageExists = Storage::disk($lookupDisk)->exists($lookupPath);
            $absolutePath = Storage::disk($lookupDisk)->path($lookupPath);
            $fileExists = file_exists($absolutePath);
            Log::info('ProcessAnalysisJob storage check', [
                'job_id' => $job->id,
                'lookup_disk' => $lookupDisk,
                'lookup_path' => $lookupPath,
                'storage_exists' => $storageExists ? 'true' : 'false',
                'absolute_path' => $absolutePath,
                'file_exists' => $fileExists ? 'true' : 'false',
                'video_asset_id' => $videoAsset->id,
                'stored_filename' => $videoAsset->stored_filename,
            ]);
            $filePath = $absolutePath;
            if (! $storageExists && ! $fileExists) {
                // Fallback: try alternative disk or re-create from VideoAsset if possible
                Log::warning('ProcessAnalysisJob: primary storage missing, trying fallback', [
                    'job_id' => $job->id,
                    'lookup_path' => $lookupPath,
                ]);
                // Try public disk as fallback (in case file was stored via public)
                if (Storage::disk('public')->exists($lookupPath)) {
                    $filePath = Storage::disk('public')->path($lookupPath);
                    Log::info('ProcessAnalysisJob fallback to public disk', ['job_id' => $job->id, 'fallback_path' => $filePath]);
                } elseif (! $storageExists) {
                    // Do not fail immediately for missing file if VideoAsset exists and was recently created
                    // Instead, attempt to proceed and let AiServiceClient handle file not found with proper error
                    // This removes the stale "Video file missing at" failure path that was incorrectly failing even when Storage::exists was true in some contexts
                    Log::warning('ProcessAnalysisJob: file not found on any disk, but VideoAsset exists - proceeding to AiServiceClient for proper handling', [
                        'job_id' => $job->id,
                        'video_asset_id' => $videoAsset->id,
                    ]);
                    // Do not return here; let the AiServiceClient handle the missing file with a more accurate error
                    // The previous stale path would incorrectly fail even when the file was actually present in some environments due to disk root mismatch
                }
            }
            // Verify file is readable before proceeding, but do not use the stale failure message
            if (! file_exists($filePath) || ! is_readable($filePath) || filesize($filePath) === 0) {
                Log::warning('ProcessAnalysisJob: file not readable or empty, failing with accurate reason', [
                    'job_id' => $job->id,
                    'filePath' => $filePath,
                    'readable' => is_readable($filePath) ? 'true' : 'false',
                    'size' => file_exists($filePath) ? filesize($filePath) : 'n/a',
                ]);
                $job->update(['status' => 'failed', 'failure_reason' => 'Video file not readable or empty', 'failed_at' => now()]);

                return;
            }
            $job->update(['status' => 'processing', 'started_at' => now(), 'progress_percent' => 5]);
            // Prevent duplicate submission via correlation_id / remote_job_id
            if ($job->remote_job_id) {
                Log::info('Duplicate submission prevented', ['job_id' => $job->id, 'remote_job_id' => $job->remote_job_id]);

                return;
            }
            // Prepare metadata for secure transfer
            $mimeType = $videoAsset->mime_type ?? mime_content_type($filePath) ?: 'video/mp4';
            $fileSize = filesize($filePath);
            $checksum = hash_file('sha256', $filePath);
            $modelVersion = $job->modelVersion ? $job->modelVersion->weight_filename : 'yolo11n.pt';
            $config = $job->config ?? ['width' => 640, 'height' => 360, 'process_every_n_frames' => 3];
            $result = $client->createRecordedJob($filePath, $videoAsset->original_filename, $correlationId, $mimeType, $fileSize, $checksum, $modelVersion, $config, $job->id);
            $remoteId = $result['job_id'] ?? null;
            if (! $remoteId) {
                throw new \RuntimeException('No remote job ID returned');
            }
            $job->update(['remote_job_id' => $remoteId, 'remote_status' => $result['status'] ?? 'processing', 'progress_percent' => $result['progress_percent'] ?? 10, 'correlation_id' => $correlationId]);
            // Poll for completion (AI service processes synchronously, but we poll to sync)
            $attempts = 0;
            while ($attempts < 30) {
                if (! app()->environment('testing')) {
                    sleep(2);
                }
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
                if (app()->environment('testing')) {
                    break;
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
            $evidenceData = null;
            try {
                $evidenceData = $client->getEvidence($remoteId, $correlationId);
            } catch (\Throwable $e) {
                Log::warning('evidence fetch failed, fallback to FS only', ['job_id' => $job->id, 'error' => $e->getMessage()]);
                $evidenceData = ['data' => []];
            }
            $evidenceByEventId = [];
            foreach (($evidenceData['data'] ?? []) as $ed) {
                if (isset($ed['event_id'])) {
                    $evidenceByEventId[$ed['event_id']] = $ed;
                }
            }
            $imported = 0;
            foreach (($eventsData['data'] ?? $eventsData['events'] ?? []) as $ev) {
                $verifiedBbox = $ev['bbox'] ?? $ev['associated_track_bbox'] ?? null;
                if (isset($ev['detections']) && is_array($ev['detections']) && count($ev['detections']) > 1) {
                    $persons = array_filter($ev['detections'], fn ($d) => ($d['class_id'] ?? $d['class'] ?? 0) == 0);
                    if (! empty($persons)) {
                        usort($persons, fn ($a, $b) => ($b['confidence'] ?? 0) <=> ($a['confidence'] ?? 0));
                        $verifiedBbox = $persons[0]['bbox'] ?? $verifiedBbox;
                    }
                }
                if ($verifiedBbox && isset($verifiedBbox['x_max']) && $verifiedBbox['x_max'] <= 1.0 && $verifiedBbox['x_max'] > 0) {
                    $verifiedBbox = [
                        'x_min' => $verifiedBbox['x_min'] * 640,
                        'y_min' => $verifiedBbox['y_min'] * 360,
                        'x_max' => $verifiedBbox['x_max'] * 640,
                        'y_max' => $verifiedBbox['y_max'] * 360,
                    ];
                    $ev['bbox'] = $verifiedBbox;
                    $ev['associated_track_bbox'] = $verifiedBbox;
                }
                $raw = $ev['event_type'] ?? $ev['event_code'] ?? 'D2';
                $typeMap = ['Mobile Phone Detected'=>'D2','Person Detected'=>'D1','Multiple Persons Detected'=>'D3','Repeated Looking Left'=>'B1','Looking Left'=>'B1','Repeated Looking Right'=>'B2','Looking Right'=>'B2','Looking Backward'=>'B3','Leaving Seat'=>'B4','Possible Seat Departure'=>'B4','Excessive Head Movement'=>'B5','Normal'=>'S1','Insufficient Evidence'=>'S2','Tracking Lost'=>'S3'];
                $code = $ev['event_code'] ?? $typeMap[$raw] ?? (in_array($raw, ['D1','D2','D3','B1','B2','B3','B4','B5','S1','S2','S3']) ? $raw : 'B1');
                $mapped = $code;
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
                $evEvidence = $evidenceByEventId[$ev['event_id'] ?? ''] ?? null;
                $this->copyEvidence($job, $detection, $ev, $correlationId, $evEvidence);
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

    private function copyEvidence(AnalysisJob $job, DetectionEvent $event, array $evData, string $correlationId, ?array $evidenceMeta = null): void
    {
        try {
            if ($evidenceMeta === null || empty($evidenceMeta['evidence_id'])) {
                Log::warning('Evidence copy skipped: no evidenceMeta for event', ['event_id' => $event->id, 'remote_event_id' => $evData['event_id'] ?? null]);
                return;
            }
            $aiEvidenceBase = base_path('../ai-service/evidence');
            $remoteJobId = $job->remote_job_id;
            $aiEvidencePath = $aiEvidenceBase.'/'.$remoteJobId;
            $resolved = realpath($aiEvidencePath) ?: $aiEvidencePath;
            $expectedChecksum = $evidenceMeta['checksum_sha256'] ?? $evidenceMeta['file_checksum'] ?? null;
            $evidenceId = $evidenceMeta['evidence_id'];
            $fileName = $evidenceMeta['file_name'] ?? null;
            $storagePath = $evidenceMeta['storage_path'] ?? null;
            $src = null;
            if ($storagePath && file_exists($storagePath)) {
                $src = $storagePath;
            } elseif ($fileName && $remoteJobId && is_dir($resolved)) {
                $candidate = $resolved.'/'.$fileName;
                if (file_exists($candidate)) {
                    $src = $candidate;
                } else {
                    $globMatch = glob($resolved.'/*'.$evidenceId.'*.jpg');
                    if (!empty($globMatch)) $src = $globMatch[0];
                }
            } elseif ($remoteJobId && is_dir($resolved)) {
                $globMatch = glob($resolved.'/*'.$evidenceId.'*.jpg');
                if (!empty($globMatch)) $src = $globMatch[0];
            }
            if (!$src || !file_exists($src)) {
                Log::warning('Evidence file not found for event', ['event_id' => $event->id, 'evidence_id' => $evidenceId, 'file_name' => $fileName]);
                return;
            }
            $actualChecksum = hash_file('sha256', $src);
            if ($expectedChecksum && strtolower($actualChecksum) !== strtolower($expectedChecksum)) {
                Log::warning('Evidence checksum mismatch', ['event_id' => $event->id, 'evidence_id' => $evidenceId, 'expected' => substr($expectedChecksum,0,12), 'actual' => substr($actualChecksum,0,12)]);
            }
            $bboxForOverlay = $evidenceMeta['bbox'] ?? $evData['bbox'] ?? $evData['associated_track_bbox'] ?? null;
            $validatedBbox = $this->validateBbox($bboxForOverlay);
            if (in_array($event->event_type, ['S3','B4']) && $validatedBbox === null) {
                Log::warning('S3/B4 invalid bbox - marking evidence unavailable', ['event_id' => $event->id, 'event_type' => $event->event_type, 'bbox' => $bboxForOverlay]);
                return;
            }
            $destDir = 'evidence/'.$job->id;
            $destFilename = $event->id.'_'.basename($src);
            $destPath = $destDir.'/'.$destFilename;
            Storage::disk('local')->makeDirectory($destDir);
            Storage::disk('local')->put($destPath, file_get_contents($src));
            $localChecksum = hash_file('sha256', Storage::disk('local')->path($destPath));
            if (strtolower($localChecksum) !== strtolower($actualChecksum)) {
                Log::warning('Local copy checksum mismatch', ['event_id' => $event->id, 'src_hash' => substr($actualChecksum,0,12), 'local_hash' => substr($localChecksum,0,12)]);
            }
            try {
                $hasBbox = \Illuminate\Support\Facades\Schema::hasColumn('event_evidence', 'bbox_json');
                $hasType = \Illuminate\Support\Facades\Schema::hasColumn('event_evidence', 'event_type');
                $hasDebug = \Illuminate\Support\Facades\Schema::hasColumn('event_evidence', 'debug_json');
            } catch (\Throwable $e) {
                $hasBbox = false; $hasType = false; $hasDebug = false;
            }
            $evDataForInsert = [
                'detection_event_id' => $event->id,
                'file_path' => $destPath,
                'file_type' => 'snapshot',
                'frame_number' => $event->started_at_frame,
                'captured_at_seconds' => $evData['timestamp_seconds'] ?? $evData['start_time'] ?? null,
                'width' => $evidenceMeta['rendered_frame_size']['width'] ?? 640,
                'height' => $evidenceMeta['rendered_frame_size']['height'] ?? 360,
                'checksum_sha256' => $localChecksum,
            ];
            if ($hasBbox) {
                $evDataForInsert['bbox_json'] = $validatedBbox ? json_encode($validatedBbox) : null;
            }
            if ($hasType) {
                $evDataForInsert['event_type'] = $event->event_type;
            }
            if ($hasDebug) {
                $debug = [
                    'remote_event_id' => $evData['event_id'] ?? null,
                    'evidence_id' => $evidenceId,
                    'event_code' => $event->event_type,
                    'track_id' => $event->temporary_track_id,
                    'event_frame_number' => $event->started_at_frame,
                    'source_bbox' => $bboxForOverlay,
                    'bbox_format' => $evidenceMeta['bbox_format'] ?? 'xyxy',
                    'rendered_bbox' => $validatedBbox,
                    'original_frame_size' => $evidenceMeta['original_frame_size'] ?? null,
                    'rendered_frame_size' => $evidenceMeta['rendered_frame_size'] ?? ['width'=>640,'height'=>360],
                    'last_valid_detection_frame' => $evidenceMeta['last_valid_detection_frame'] ?? null,
                    'absence_frames' => $evidenceMeta['absence_frames'] ?? null,
                    'SHA256' => $localChecksum,
                ];
                $evDataForInsert['debug_json'] = json_encode($debug);
            }
            EventEvidence::create($evDataForInsert);
            $event->update(['evidence_available' => true]);
            Log::info('copyEvidence deterministic mapping', [
                'event_id' => $event->id,
                'remote_event_id' => $evData['event_id'] ?? null,
                'evidence_id' => $evidenceId,
                'event_type' => $event->event_type,
                'frame_number' => $event->started_at_frame,
                'src' => basename($src),
                'checksum' => substr($localChecksum,0,12),
                'bbox' => $validatedBbox,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Evidence copy failed', ['event_id' => $event->id, 'error' => $e->getMessage()]);
        }
    }

    private function validateBbox(?array $bbox): ?array
    {
        if (!$bbox || !isset($bbox['x_min'], $bbox['y_min'], $bbox['x_max'], $bbox['y_max'])) return null;
        $xMin = (float)$bbox['x_min']; $yMin = (float)$bbox['y_min']; $xMax = (float)$bbox['x_max']; $yMax = (float)$bbox['y_max'];
        if ($xMax <= $xMin || $yMax <= $yMin) return null;
        if ($xMin < 0 || $yMin < 0) return null;
        $w = $xMax - $xMin; $h = $yMax - $yMin;
        if ($w < 10 || $h < 10 || $w*$h < 500) return null;
        $xMin = max(0, min($xMin, 639)); $yMin = max(0, min($yMin, 359)); $xMax = max(0, min($xMax, 639)); $yMax = max(0, min($yMax, 359));
        if ($xMax <= $xMin || $yMax <= $yMin) return null;
        return ['x_min'=>$xMin,'y_min'=>$yMin,'x_max'=>$xMax,'y_max'=>$yMax];
    }

    private function sanitizeError(string $msg): string
    {
        // Remove secrets, limit length, no stack trace
        $msg = preg_replace("/(token|password|secret|key)=[^&\s]+/i", '$1=[REDACTED]', $msg) ?? $msg;

        return substr($msg, 0, 500);
    }
}
