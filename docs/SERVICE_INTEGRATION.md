# Service Integration

## Typed AI Service Client
- `app/Services/AiServiceClient.php` with typed methods: `healthCheck()`, `createRecordedJob(filePath, originalFilename, correlationId)`, `getJob(jobId, correlationId)`, `getEvents(jobId)`, `getMetrics(jobId)`, `cancelJob(jobId)`, `retryJob(jobId)`
- Configurable via `config/ai.php` (`AI_SERVICE_BASE_URL` default http://127.0.0.1:8001, `AI_SERVICE_TOKEN` default dev-token-change-me, `AI_SERVICE_TIMEOUT` 10s, `connect_timeout` 5s, `retry_attempts` 2, `retry_delay_ms` 200)
- Service authentication: `withToken` if token not default, 401 mapped to "AI authentication failed"
- Timeouts: 10s default, 5s connect, 300s for createRecordedJob (but queued job handles, controller not blocking), retry only safe operations (GET health, getJob) via `retry()` with ConnectionException check, POST with file not retried
- Correlation ID: `Str::uuid()` per request, sent as `X-Correlation-Id` header, stored in `analysis_jobs.correlation_id`, logged with every AI call
- Structured error mapping: `AiServiceException` with `statusCode` and `details`, 401? auth, 422? invalid video, 404? not found, 409? conflict, 500? internal, sanitized via `redact()` (removes token/password/secret/key=[REDACTED]), logged via `Log::error` with redacted message
- Secret redaction: `redact()` replaces `(token|password|secret|key)=...` with `[REDACTED]`, never logs raw token, never exposes in views/API
- Health check: `GET /api/v1/health` with correlation ID, maps to 503 if unavailable, dashboard route `GET /health/ai` proxies

## No Synchronous AI Processing
- `AnalysisJobController@store` creates DB record and dispatches `ProcessAnalysisJob` (ShouldQueue, database queue, timeout 600), returns redirect immediately, no `await` on AI service
- `ProcessAnalysisJob` handles AI call in background, updates DB, polls, imports events, copies evidence, handles failures

## Job Workflow Integration
- Upload validated video ? create dashboard job (pending) ? submit to AI service via `createRecordedJob` (multipart file) ? store `remote_job_id` ? poll `getJob` every 2s ? synchronize progress (update `progress_percent` from remote, not invented) ? import event metadata idempotently ? link evidence safely via `Storage::disk("local")` outside public ? handle failed (update `failed`, `failure_reason` sanitized) / cancelled (update `cancelled`) / duplicate (check recent within 5 min) / retry (new job with new correlation_id)

## Evidence Delivery
- Prefer `Laravel-controlled protected copy`: AI service evidence at `../ai-service/evidence/{remote_job_id}/*.jpg` copied to `storage/app/evidence/{job_id}/{event_id}.jpg` via `Storage::disk("local")->put`, checksum, `file_path` stored outside public, served via `EvidenceController@show` with `auth` + `role` + `hasAnyRole` + `str_contains(..)` check, `Storage::exists` check, audit `evidence_accessed`, no absolute path exposed, no user-supplied path
- Alternative `Time-limited authenticated AI-service retrieval` not implemented (would require AI service endpoint `GET /api/v1/jobs/{id}/evidence/{eid}` with token)
- Never expose absolute paths, never serve without authorization

## Error Handling
- `AiServiceException` mapped to user-friendly messages, 503 for unavailable, 401 for auth, 422 for invalid, sanitized, logged with correlation_id, not exposing secrets
- `ProcessAnalysisJob` catches `AiServiceException` and generic, updates job to `failed` with sanitized reason, logs, audits

## Testing with Http::fake
- Tests use `Http::fake()` to mock AI service: `Http::fake(["*"=> Http::response([...],200)])` or `Http::fake(function($request){ return Http::response([...],401); })`, correlation ID verified, no real AI service needed for unit tests
