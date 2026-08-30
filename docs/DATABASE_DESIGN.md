# Database Design

## ER Diagram (Mermaid)

```mermaid
erDiagram
    users ||--o{ audit_logs : performs
    users ||--o{ review_decisions : makes
    roles ||--o{ users : assigned
    permissions ||--o{ roles : granted
    exam_rooms ||--o{ exam_sessions : hosts
    exam_sessions ||--o{ video_assets : has
    exam_sessions ||--o{ camera_sources : linked
    exam_sessions ||--o{ analysis_jobs : contains
    exam_sessions ||--o{ detection_events : generates
    model_versions ||--o{ analysis_jobs : used_by
    model_versions ||--o{ detection_events : produced_by
    analysis_jobs ||--o{ detection_events : creates
    analysis_jobs ||--o{ processing_metrics : measures
    detection_events ||--o{ event_evidence : has
    detection_events ||--o{ review_decisions : reviewed
    users ||--o{ retention_actions : executes

    users { bigint id PK; string email UK; string password_hash; datetime created_at }
    roles { bigint id PK; string name UK }
    permissions { bigint id PK; string name UK }
    exam_rooms { bigint id PK; string name UK; string building }
    exam_sessions { bigint id PK; bigint exam_room_id FK; string status; datetime started_at }
    camera_sources { bigint id PK; bigint exam_session_id FK; string source_type; string identifier_encrypted }
    video_assets { bigint id PK; bigint exam_session_id FK; string stored_filename; string original_filename; string mime_type }
    analysis_jobs { bigint id PK; bigint exam_session_id FK; string source_type; string status; json config }
    model_versions { bigint id PK; string name; string checksum_sha256 UK; string license }
    detection_events { bigint id PK; bigint exam_session_id FK; string event_type; string review_status }
    event_evidence { bigint id PK; bigint detection_event_id FK; string file_path; string file_type }
    review_decisions { bigint id PK; bigint detection_event_id FK; bigint reviewed_by FK; string decision }
    processing_metrics { bigint id PK; bigint analysis_job_id FK; float processing_fps }
    audit_logs { bigint id PK; bigint actor_id FK; string action; string target_type }
    retention_actions { bigint id PK; bigint actor_id FK; string action; string target_type }
```

## Tables

### users
Purpose: Authentication principals. Columns: `id` bigint PK auto-inc, `name` varchar(255) NOT NULL, `email` varchar(255) NOT NULL UNIQUE, `email_verified_at` datetime NULL, `password` varchar(255) NOT NULL (bcrypt), `remember_token` varchar(100) NULL, `created_at`/`updated_at` datetime NOT NULL. FK: none. Indexes: email UNIQUE, created_at. Deletion: restrict if audit_logs/review_decisions reference. Sensitive: password hash never serialized. Serialization: hide password, remember_token.

### roles
Purpose: RBAC roles. Columns: `id` PK, `name` varchar(100) UNIQUE NOT NULL (`system_admin`, `exam_admin`, `invigilator`, `reviewer`, `auditor`), `description` varchar(255) NULL, timestamps. Indexes: name UNIQUE. Deletion: restrict if users assigned.

### permissions
Purpose: granular permissions. Columns: `id` PK, `name` varchar(150) UNIQUE NOT NULL (e.g., `manage_rooms`, `upload_recordings`, `view_evidence`, `review_events`, `export_reports`, `view_audit_logs`), `group` varchar(100) NOT NULL, timestamps. Pivot `role_permission` (role_id, permission_id) composite PK.

### exam_rooms
Purpose: Physical rooms. Columns: `id` PK, `name` varchar(150) UNIQUE NOT NULL, `building` varchar(150) NULL, `capacity` int NULL, `camera_position_notes` text NULL, timestamps. Indexes: name UNIQUE. Deletion: restrict if exam_sessions exist.

### exam_sessions
Purpose: Logical exam occurrence. Columns: `id` PK, `exam_room_id` FK -> exam_rooms.id NULL (nullable for ad-hoc), `name` varchar(200) NOT NULL, `status` enum(`pending`,`active`,`completed`,`cancelled`) NOT NULL DEFAULT pending, `started_at` datetime NULL, `ended_at` datetime NULL, `created_by` FK -> users.id NOT NULL, timestamps. Indexes: exam_room_id, status, started_at. Deletion: cascade to video_assets, analysis_jobs, detection_events only via retention; otherwise restrict.

### camera_sources
Purpose: Registered capture sources. Columns: `id` PK, `exam_session_id` FK -> exam_sessions.id NULL, `name` varchar(200) NOT NULL, `source_type` enum(`webcam`,`rtsp`,`http`,`video_file`,`test_source`) NOT NULL, `identifier` varchar(500) NOT NULL (device index / URL without credentials), `credentials_encrypted` text NULL (AES-256-GCM, app key), `status` enum(`inactive`,`testing`,`connected`,`failed`) DEFAULT inactive, `last_tested_at` datetime NULL, `created_by` FK -> users.id, timestamps. Unique: name per session if scoped. Indexes: exam_session_id, source_type, status. Deletion: restrict if analysis_jobs reference. Sensitive: credentials_encrypted never serialized; API never returns it; only `has_credentials: bool`. Serialization restrictions: never expose identifier with embedded credentials; store URL and credentials separately.

### video_assets
Purpose: Uploaded recordings metadata. Columns: `id` PK, `exam_session_id` FK -> exam_sessions.id NOT NULL, `original_filename` varchar(255) NOT NULL (display only), `stored_filename` varchar(255) NOT NULL UNIQUE (uuid + ext, outside public path), `mime_type` varchar(100) NOT NULL, `size_bytes` bigint NOT NULL, `duration_seconds` float NULL, `width` int NULL, `height` int NULL, `fps` float NULL, `codec` varchar(50) NULL, `checksum_sha256` varchar(64) NULL, `validation_status` enum(`pending`,`valid`,`invalid`) DEFAULT pending, `validation_error` text NULL, `uploaded_by` FK -> users.id, timestamps. Indexes: exam_session_id, validation_status, created_at. Deletion: restrict if analysis_jobs exist; else cascade via retention. Sensitive: stored_filename never guessable; no path traversal. Serialization: never expose absolute disk path; only download via signed route.

### analysis_jobs
Purpose: Processing jobs (recorded or live). Columns: `id` PK, `exam_session_id` FK NOT NULL, `source_type` enum(`recorded_video`,`live_stream`,`webcam`,`test_source`) NOT NULL, `video_asset_id` FK NULL, `camera_source_id` FK NULL, `model_version_id` FK NOT NULL, `status` enum(`pending`,`queued`,`processing`,`paused`,`cancelled`,`failed`,`completed`) NOT NULL DEFAULT pending, `config` json NOT NULL (width, height, process_every_n_frames, confidence_threshold, temporal rules), `progress_percent` tinyint UNSIGNED DEFAULT 0, `started_at` datetime NULL, `completed_at` datetime NULL, `failed_at` datetime NULL, `failure_reason` text NULL, `created_by` FK -> users.id, timestamps. Constraints: CHECK (video_asset_id IS NOT NULL OR camera_source_id IS NOT NULL OR source_type='test_source'). Indexes: exam_session_id, status, source_type, model_version_id, created_at. Deletion: restrict if detection_events exist. Serialization: failure_reason sanitized (no secrets).

### model_versions
Purpose: Model registry. Columns: `id` PK, `name` varchar(100) NOT NULL (e.g., `yolo11n.pt`), `version` varchar(50) NOT NULL, `weight_filename` varchar(255) NOT NULL, `checksum_sha256` varchar(64) UNIQUE NOT NULL, `class_list` json NOT NULL, `training_dataset_version` varchar(100) NULL, `image_size` int NULL, `license` varchar(50) NOT NULL (e.g., `AGPL-3.0`), `source_url` varchar(500) NULL, `framework_versions` json NULL, `created_at` datetime NOT NULL. Indexes: checksum_sha256 UNIQUE, name+version UNIQUE. Deletion: restrict if analysis_jobs/detection_events reference.

### detection_events
Purpose: Time-stamped observable events. Columns: `id` PK, `exam_session_id` FK NOT NULL, `analysis_job_id` FK NOT NULL, `model_version_id` FK NOT NULL, `source_type` enum(`recorded_video`,`live_stream`,`webcam`,`test_source`) NOT NULL, `temporary_track_id` int NOT NULL, `event_type` enum(`D1`,`D2`,`B1`,`B2`,`B3`,`B4`) NOT NULL, `event_status` enum(`active`,`ended`) DEFAULT active, `started_at_frame` int NULL, `ended_at_frame` int NULL, `started_at_seconds` float NULL, `ended_at_seconds` float NULL, `confidence` float NULL (detector events only), `rule_score` float NULL (temporal events), `evidence_available` boolean DEFAULT false, `review_status` enum(`pending`,`confirmed_suspicious`,`dismissed_normal`,`needs_further_review`) DEFAULT pending, `reviewed_by` FK -> users.id NULL, `reviewed_at` datetime NULL, `reviewer_note` varchar(500) NULL, timestamps. Indexes: exam_session_id, analysis_job_id, event_type, review_status, started_at_seconds, temporary_track_id. Deletion: cascade to event_evidence, review_decisions. Serialization: never expose internal file paths.

### event_evidence
Purpose: Snapshots/clips per event. Columns: `id` PK, `detection_event_id` FK NOT NULL, `file_path` varchar(500) NOT NULL (outside public path), `file_type` enum(`snapshot`,`clip`) NOT NULL, `frame_number` int NULL, `captured_at_seconds` float NULL, `width` int NULL, `height` int NULL, `checksum_sha256` varchar(64) NULL, timestamps. Indexes: detection_event_id, file_type. Deletion: cascade; files deleted via retention. Sensitive: file_path never serialized directly; access via signed URL with authz.

### review_decisions
Purpose: Immutable human decisions (append-only). Columns: `id` PK, `detection_event_id` FK NOT NULL, `exam_session_id` FK NOT NULL, `reviewed_by` FK -> users.id NOT NULL, `decision` enum(`confirmed_suspicious`,`dismissed_normal`,`needs_further_review`) NOT NULL, `note` varchar(500) NULL, `created_at` datetime NOT NULL (no updated_at; append-only). Indexes: detection_event_id, reviewed_by, decision, created_at. Deletion: restrict (audit). Also updates detection_events.review_status for query convenience.

### processing_metrics
Purpose: Per-job performance metrics. Columns: `id` PK, `analysis_job_id` FK UNIQUE NOT NULL, `source_fps` float NULL, `processing_fps` float NULL, `detection_latency_ms` float NULL, `end_to_end_alert_latency_ms` float NULL, `cpu_percent` float NULL, `memory_mb` float NULL, `gpu_percent` float NULL, `dropped_frames` int DEFAULT 0, `queue_size` int NULL, `reconnect_count` int DEFAULT 0, `job_duration_seconds` float NULL, `video_duration_to_processing_ratio` float NULL, timestamps. Indexes: analysis_job_id UNIQUE. Deletion: cascade.

### audit_logs
Purpose: Security/operational audit. Columns: `id` PK, `actor_id` FK -> users.id NULL (system actions NULL), `action` varchar(100) NOT NULL (e.g., `login_success`, `video_uploaded`, `analysis_started`, `event_reviewed`), `target_type` varchar(100) NULL, `target_id` varchar(100) NULL, `ip_address` varchar(45) NULL, `user_agent` varchar(255) NULL, `correlation_id` varchar(36) NULL, `metadata` json NULL (safe before/after), `result` enum(`success`,`failure`) NOT NULL, `created_at` datetime NOT NULL (no updated_at). Indexes: actor_id, action, target_type+target_id, created_at, correlation_id. Deletion: never via cascade; retention_actions handle. Sensitive: never store passwords/tokens/camera passwords/raw secret URLs/full payloads.

### retention_actions
Purpose: Data retention/deletion workflow. Columns: `id` PK, `actor_id` FK -> users.id NULL, `action` enum(`scheduled`,`executed`,`failed`) NOT NULL, `target_type` varchar(100) NOT NULL, `target_id` varchar(100) NOT NULL, `scheduled_at` datetime NULL, `executed_at` datetime NULL, `reason` varchar(255) NULL, timestamps. Indexes: target_type+target_id, action, scheduled_at. Deletion: restrict.

## Credential Storage Strategy

- `camera_sources.credentials_encrypted` encrypted with Laravel `encrypt()` (AES-256-GCM, APP_KEY). Key rotation via `php artisan key:generate` requires re-encryption.
- `.env` never committed; `.env.example` contains placeholder `CAMERA_ENCRYPTION_KEY=`.
- API never returns credential values; dashboard shows `has_credentials: true/false` only.
- Logs redact credential fields.

## Retention Strategy

- Video assets, annotated outputs, evidence: configurable retention (e.g., 30/90 days); `retention_actions` scheduled; secure deletion (overwrite + unlink) audited.
- Audit logs: retained longer (e.g., 1 year) per institutional policy; never deleted without explicit retention action.
- Metrics: retained for research evaluation.

## Data Lifecycles

### Recorded Mode
Upload video_asset (valid) -> create analysis_job (pending->queued->processing) -> detection_events + event_evidence + processing_metrics -> review_decisions -> audit_logs -> optional report export -> retention scheduled.

### Live Mode
Register camera_source (encrypted) -> test connection (status) -> start analysis_job (live_stream) -> detection_events (streaming) -> event_evidence (incident) -> review_decisions (live review queue) -> stop -> summary metrics -> audit_logs -> retention.

### Evidence Lifecycle
Incident threshold met -> Evidence Manager captures snapshot -> stored outside public path with checksum -> linked to detection_event -> served via signed, authz-checked route -> retention deletion audited.

MVP may implement subset (users, exam_sessions, video_assets, analysis_jobs, detection_events, event_evidence, audit_logs) but schema must remain internally consistent for future phases.
