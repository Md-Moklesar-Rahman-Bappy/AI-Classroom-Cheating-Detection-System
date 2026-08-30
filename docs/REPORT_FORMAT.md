# Report Format

## Authorized Report
- Generated via `ReportController@show` (HTML) and `ReportController@download` (PDF/HTML) for `AnalysisJob`, requires role system_admin/exam_admin/reviewer/auditor, audit `report_viewed`/`report_downloaded`

## Contents
- Exam session: name, room (name, building), status, started_at
- Source mode: `source_type` (recorded_video etc) + video asset `original_filename` if present
- Analysis job: ID, status, progress, remote_job_id, correlation_id, started_at, completed_at/failed_at, failure_reason (sanitized), config (json), remote_output_metadata
- Model version: name, version, weight_filename, checksum_sha256, class_list, license, source_url, framework_versions
- Configuration: width, height, process_every_n_frames, confidence, behavior_config (window, min_supporting etc), config_version
- Events: count, table with type (D1/D2/B1-B4), track ID, review_status (pending/confirmed/dismissed/needs), confidence/rule_score, started/ended frame/seconds, evidence_available
- Human review state: per event `review_status`, `reviewed_by`, `reviewed_at`, `reviewer_note`, `ReviewDecision` history
- Metrics: source_fps, processing_fps, detection_latency_ms, cpu_percent, memory_mb, dropped_frames, job_duration, ratio (if available) from `processing_metrics`
- Disclaimer: "AI-generated alerts indicate observable events that require human review. An alert is not proof of academic misconduct." + "This report contains AI-generated observations that require human review. An alert is not proof of academic misconduct."

## Do Not State
- Never state AI alerts prove cheating (explicitly forbidden)

## Format
- HTML view `reports.show` with Bootstrap cards, badges text+color, tables, disclaimer alert-warning
- PDF view `reports.pdf` simple HTML for DomPDF fallback, or HTML download if DomPDF not installed
- No absolute paths, no secrets, safe serialization

## Access
- `GET /analysis-jobs/{id}/report` and `/report/download` with `auth` + `verified` + role check, 403 if unauthorized
