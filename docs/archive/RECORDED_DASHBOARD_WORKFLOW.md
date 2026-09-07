# Recorded Dashboard Workflow

## End-to-End Authorized Flow
Login ? Create Exam Session ? Upload Video ? Create Analysis Job ? AI Service Processes ? Dashboard Displays Progress ? Events and Evidence Appear ? Reviewer Records Decision ? Audit Log Records Actions ? Authorized Report Exported

## Steps

### 1. Login
- `POST /login` with email/password, rate limiting (throttle:5), session regeneration, audit `login_success`
- Roles: system_admin, exam_admin, invigilator, reviewer, auditor (via RoleMiddleware)

### 2. Create Exam Session
- `POST /exam-sessions` with `name`, `exam_room_id`, `status` (pending/active/completed/cancelled), `created_by=auth()->id()`
- Foreign key to exam_rooms, indexed, audit `session_created`

### 3. Upload Video
- `POST /video-assets` with `exam_session_id`, `video` (file, mimes:mp4,avi,mov,mkv, max 512000 KB ~500MB, MIME validated via fileinfo, not just extension)
- Generate storage name `Str::uuid().ext`, preserve original only as `original_filename` metadata, store via `Storage::disk("local")->put("video_assets/".$stored)` outside public path (`storage/app/video_assets`), never pass arbitrary local path from browser, use controlled asset IDs
- Validation: size, MIME, readable, not empty (via `$file->getSize()`), `validation_status=valid`, audit `video_uploaded`, clean abandoned temp files >1h not referenced in DB

### 4. Create Analysis Job
- `POST /analysis-jobs` with `exam_session_id`, `source_type=recorded_video`, `model_version_id`, `video_asset_id` (optional for test_source)
- Duplicate prevention: check same session+video+model within 5 minutes and status pending/queued/processing ? reject with 422
- Create `AnalysisJob` with `status=pending`, `config` (width 640, height 360, process_every_n_frames 3, confidence 0.25), `correlation_id=Str::uuid()`, `created_by`
- Dispatch `ProcessAnalysisJob` (ShouldQueue, timeout 600, tries 1) - **no AI processing inside synchronous controller**, web request returns immediately with redirect to `analysis-jobs.show` and message "Job queued (ID X). Refresh to see progress."

### 5. AI Service Processes Video
- `ProcessAnalysisJob` handles: validates video file exists, updates job to `processing`/`queued`, calls `AiServiceClient::createRecordedJob` with file contents and original filename, stores `remote_job_id` (UUID from AI service), `remote_status`, `correlation_id`
- Polls `getJob` every 2s up to 30 attempts (60s), updates `remote_status`, `remote_progress`, `progress_percent` (never invent, only from AI service)
- On `completed`: calls `getEvents` and `getMetrics`, imports events idempotently (check `event_id` exists or duplicate by job+track+type+start_frame), creates `DetectionEvent` with `event_type` mapped (Mobile Phone Detected?D2, Repeated Looking Left?B1 etc), `review_status=pending`, then copies evidence from `ai-service/evidence/{remote_job_id}/*.jpg` to `storage/app/evidence/{job_id}/{event_id}.jpg` via `Storage::disk("local")`, creates `EventEvidence` with `file_path` (outside public), `checksum_sha256`, `evidence_available=true`
- On `failed`/`cancelled`: updates `status`, `failure_reason` (sanitized, 500 chars, no secrets), `failed_at`
- Handles `failed service`, `cancelled job`, `duplicate submission` (checked before dispatch), `safe retry` (new job with same params, new correlation_id)

### 6. Dashboard Displays Progress
- `GET /analysis-jobs/{id}` shows: Status (Pending/Queued/Processing/Cancelling/Cancelled/Failed/Completed) with badge text+color, Progress bar (0-100% from `progress_percent` / `remote_progress`, not invented), Processed frames where available (via metrics), Started/Completed/Failed times, Failure summary (sanitized), Retry option when `failed`/`cancelled`, Configuration (json), Model version (name, checksum, license), Correlation ID, Remote ID, Metrics (if available), Events count
- Auto-sync: if `remote_job_id` exists and status not terminal, `show` does `getJob` sync to update `remote_status`/`progress`
- Manual Sync: `POST /analysis-jobs/{id}/sync` calls `getJob` and if completed and no events, dispatches `SyncAnalysisJob`
- No long-running request: controller returns view immediately, polling via manual Sync or page refresh, not waiting for AI completion

### 7. Events and Evidence Appear
- `GET /analysis-jobs/{id}` events table, `GET /detection-events?job={id}` filter, `GET /detection-events/{id}` detail with 4 sections
- Sync prevents duplicates via stable `event_id` (from AI service) or idempotent key (job+track+type+start_frame)
- Preserved: source timestamp, track ID, event type (D1/D2/B1-B4), machine evidence (confidence, bbox, rule_score), model version, config version, review status

### 8. Reviewer Records Decision
- `POST /detection-events/{id}/review` with `decision` (confirmed_suspicious/dismissed_normal/needs_further_review) and `note` (required for confirmed/needs if approved), only reviewer/system_admin/exam_admin, creates `ReviewDecision` (append-only), updates `DetectionEvent` review_status/reviewed_by/reviewed_at/reviewer_note, audit `event_reviewed`

### 9. Audit Log
- `AuditHelper::log` for job_created, job_completed/failed/cancelled/retry, video_uploaded, room/session/camera created, event_reviewed, report_viewed/downloaded, evidence_accessed, etc., with actor_id, IP, result, metadata (no secrets)

### 10. Authorized Report Exported
- `GET /analysis-jobs/{id}/report` and `/report/download` require role system_admin/exam_admin/reviewer/auditor, show HTML/PDF with exam session, source mode, job, model version, config, events, human review state, metrics, disclaimer: "AI-generated alerts indicate observable events that require human review. An alert is not proof of academic misconduct."
- Do not state AI alerts prove cheating

## Upload Safety Summary
- MIME and size validated, storage name uuid, original preserved only as metadata, outside public, no arbitrary path, controlled asset IDs, temp cleaned
