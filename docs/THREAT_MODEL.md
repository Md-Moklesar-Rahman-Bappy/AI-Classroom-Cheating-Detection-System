# Threat Model

Method: STRIDE-inspired; each threat documents Asset, Threat, Attack path, Impact, Existing control, Required control, Verification test, Residual risk.

## T01 Account Compromise
- Asset: users, sessions, evidence
- Threat: Credential stuffing/brute force on login
- Attack path: Attacker tries common passwords against `/login`
- Impact: High - full account takeover, evidence access
- Existing control: None (Phase 1 design)
- Required: Strong password policy (Bcrypt 12), login rate limiting (5/min/IP), lockout after 5 failures, audit `login_failure`, HTTPS.
- Verification: Feature test: 6th login in 5 min returns 429; audit log increments.
- Residual: Low if rate limit + lockout enforced.

## T02 Privilege Escalation
- Asset: roles/permissions, evidence, camera_sources
- Threat: Invigilator accesses System Administrator functions via direct URL/ID enumeration
- Attack path: `/admin/users` or `/evidence/{id}` without role check
- Impact: High - unauthorized admin actions
- Existing: None
- Required: Server-side authorization (Policies/Gates) on every protected action; never rely on hidden buttons; id enumeration prevented via UUIDs + authz.
- Verification: Test: invigilator GET /admin/users -> 403; reviewer GET /evidence/{other_session} -> 403.
- Residual: Low.

## T03 Unauthorized Evidence Access
- Asset: event_evidence files
- Threat: Direct URL access to `/storage/evidence/{file}` without auth
- Attack path: Guessing stored_filename or traversing path
- Impact: High - privacy breach
- Existing: .gitignore excludes evidence; not yet access-controlled
- Required: Store outside public path; serve via signed route with `auth` + `can:viewEvidence`; audit `evidence_viewed`.
- Verification: Unauthenticated GET evidence -> 401; wrong-session user -> 403; audit entry exists on success.
- Residual: Low.

## T04 Camera Credential Exposure
- Asset: camera_sources.credentials_encrypted, RTSP URLs
- Threat: Credentials returned in API or logged
- Attack path: `GET /api/camera_sources` returns password; logs contain `rtsp://user:pass@host`
- Impact: Critical - camera hijack, privacy violation
- Existing: .gitignore excludes .env
- Required: Encrypt at rest; never serialize credential values; API returns `has_credentials` bool only; logs redact; credential abstraction layer.
- Verification: API response body never contains `password` or `credentials_encrypted`; log grep for `rtsp://` with password returns 0; test fails if exposed.
- Residual: Low.

## T05 Malicious Video Upload
- Asset: video_assets, server filesystem
- Threat: Uploaded file is executable or malformed to exploit decoder
- Attack path: Upload `shell.php` renamed to `video.mp4`
- Impact: High - RCE or DoS
- Existing: .gitignore excludes uploads
- Required: MIME validation (not extension alone), file-type whitelist (mp4, avi, mov, mkv), size limit, store outside executable public dir, readability check via OpenCV, virus scan optional.
- Verification: Upload `.php` with mp4 name -> 422; upload 5GB file -> 422; stored file not executable.
- Residual: Low.

## T06 Path Traversal
- Asset: video_assets.stored_filename, event_evidence.file_path
- Threat: `../../etc/passwd` via filename
- Attack path: Client provides `original_filename` with traversal; server trusts it for storage path
- Impact: High - file overwrite/read
- Existing: stored_filename generated as uuid + ext
- Required: Never use client-provided path for storage; generate safe name; validate with `pathlib` + `is_safe` check.
- Verification: Upload with `../../evil.mp4` name stores as `uuid.mp4`; file not outside intended dir.
- Residual: Low.

## T07 Executable Upload
- Asset: Server execution
- Threat: Uploaded video is actually executable with double extension
- Attack path: `video.mp4.exe` or polyglot file
- Impact: High - execution if served
- Existing: MIME check planned
- Required: Whitelist MIME + extension, execute permission never set on upload dir, `Content-Disposition: attachment` on download.
- Verification: `video.mp4.exe` -> 422; upload dir `ls -l` shows no `x` bit.

## T08 API Spoofing
- Asset: AI service -> Laravel trust
- Threat: Attacker calls AI service directly with forged service token
- Attack path: Guess or leak `AI_SERVICE_TOKEN`
- Impact: High - fake jobs/events
- Existing: Service token in .env
- Required: Strong random token (32+ bytes), rotation, rate limiting, IP allowlist if possible, audit all service calls.
- Verification: Request without token -> 401; with wrong token -> 401; audit logs service calls.

## T09 Replay / Duplicate Requests
- Asset: analysis_jobs, detection_events
- Threat: Replay `POST /jobs/recorded` to create duplicate jobs or double-charge metrics
- Attack path: Capture valid request and resend
- Impact: Medium - resource waste, duplicate events
- Existing: None
- Required: Idempotency-Key header (24h store); duplicate key with same payload returns same job_id; different payload -> 422.
- Verification: Two POSTs with same Idempotency-Key -> same job_id; different payload same key -> 422.

## T10 Stream Injection
- Asset: Live camera stream
- Threat: Attacker injects frames into RTSP/HTTP stream (MITM or fake source)
- Attack path: Compromise network, inject alert-triggering frames
- Impact: Medium - false alerts or suppressed true alerts
- Existing: None
- Required: RTSP over TLS where supported, credential encryption, stream health metrics (FPS drop, reconnect count), alert correlation IDs.
- Verification: Simulated stream interruption -> health shows reconnect_count increment; alert latency spike logged.

## T11 Dependency Compromise
- Asset: Supply chain (ultralytics, pip, composer, npm)
- Threat: Typosquatting or compromised package
- Impact: Critical - RCE, data theft
- Existing: requirements.txt pinned
- Required: Pin versions, hash-check (`pip --require-hashes` optional), Dependabot (pip/composer/npm/github-actions), `composer audit` / `pip audit`, lock files committed.
- Verification: `pip audit` / `npm audit` in CI; Dependabot PRs reviewed.

## T12 Model-File Tampering
- Asset: yolo11n.pt weights
- Threat: Attacker replaces weight file with malicious pickle or poisoned model
- Impact: High - arbitrary code exec via deserialization, degraded detection
- Existing: *.pt excluded from Git; checksum not yet verified
- Required: Store checksum_sha256 in model_versions; verify on load; never deserialize untrusted pickle without understanding risk; weights outside public path.
- Verification: Test: tampered file checksum mismatch -> model load fails with safe error, audit logged.

## T13 Prompt / AI Concerns (LLM future)
- Asset: N/A (no LLM in MVP)
- Threat: If LLM added for report generation, prompt injection could alter reports
- Attack path: User note contains injection to change report content
- Impact: Medium if LLM introduced
- Existing: No LLM
- Required: If LLM later introduced, sanitize reviewer_note, use parameterized prompts, never trust LLM output for authorization, add THREAT_MODEL update.
- Verification: N/A for MVP; future test: injection string in note does not alter report structure.

## T14 Audit Tampering
- Asset: audit_logs
- Threat: Attacker deletes or modifies audit entries to hide actions
- Attack path: Direct DB access or exploit to `DELETE FROM audit_logs`
- Impact: High - loss of accountability
- Existing: audit_logs append-only design
- Required: DB user for app has no DELETE on audit_logs; retention only via retention_actions with separate privileged role; audit logs immutable; hash chain optional.
- Verification: App DB user `DELETE FROM audit_logs` -> permission denied; test fails if deletable.

## T15 Dataset Leakage
- Asset: datasets, staged recordings
- Threat: Real participant videos or personal data committed to Git or exposed via public URL
- Impact: High - privacy, legal
- Existing: .gitignore excludes datasets, evidence, weights, .env
- Required: Dataset dirs outside repo or explicitly gitignored; DVC or manifest for versioning; no real exam footage without approval; staged adult participants only.
- Verification: `git status` after placing dummy video in `datasets/` shows ignored; test: `git add datasets/` -> nothing added.

## T16 Cross-Session Access
- Asset: exam_sessions, detection_events
- Threat: User from session A accesses events from session B via ID enumeration
- Attack path: `GET /api/v1/events?exam_session_id=<other_session_uuid>`
- Impact: High - privacy, integrity
- Existing: exam_session_id required param
- Required: Authorization check: user must have permission for that exam_session; query scoped to authorized sessions.
- Verification: User with session A tries to fetch session B events -> 403 or empty with audit.

## T17 Debug Information Exposure
- Asset: Error responses, logs
- Threat: Stack traces, file paths, secrets leaked in 500 responses when DEBUG=true
- Impact: Medium - info disclosure aiding further attacks
- Existing: None
- Required: Production `APP_DEBUG=false` / `DEBUG=false`; safe error responses (generic message + correlation_id); detailed logs only server-side with redaction; CI checks DEBUG not true in prod config.
- Verification: With DEBUG=false, 500 response body does not contain `Traceback` or file paths; test asserts.

## T18 Denial of Service
- Asset: AI service, dashboard
- Threat: Large video upload or many concurrent jobs exhaust 8GB RAM / CPU
- Impact: High - service unavailable
- Existing: Single-camera, process-every-3rd-frame mitigates partially
- Required: Upload size limit, concurrent job limit (1 active), queue (pending/queued), timeout (e.g., 30 min/job), memory monitor with auto-pause, rate limiting.
- Verification: Upload 10 concurrent jobs -> 429 or queued; memory monitor test: simulate high memory -> job pauses.

## T19 Retention Failure
- Asset: video_assets, event_evidence, audit_logs
- Threat: Evidence deleted too early or retained too long violating policy
- Impact: Medium - legal, privacy
- Existing: retention_actions table planned
- Required: Configurable retention periods (30/90 days video/evidence, 1 year audit); scheduled retention_actions; secure deletion (overwrite + unlink) audited; never delete audit without retention action.
- Verification: Expired evidence file still exists but retention_actions shows `scheduled`; after execution, file gone and audit logged.

## T20 Unauthorized Export
- Asset: Reports, evidence, metrics
- Threat: User without export permission downloads report with sensitive data
- Attack path: `GET /api/reports/{session_id}` without `export_reports` permission
- Impact: Medium - data exfiltration
- Existing: Roles/permissions planned
- Required: `can:exportReports` middleware; audit `report_exported`; signed URL with expiry.
- Verification: Reviewer without export permission -> 403 on export; audit entry only on success for authorized user.

## Residual Risk Summary

After required controls, residual risk is Low for all threats except T08 (API spoofing) and T11 (dependency compromise) which remain Low-Medium due to external factors; mitigated by token rotation, IP allowlist, and audit tooling. No High residual risks remain if controls implemented and verification tests pass.
