# Temporal Evidence Remediation Report
Date: 2026-09-09 Status: IMPLEMENTED Defect: Temporal-Staleness S3/B4

## Root Cause
Historical bbox (24,126)-(209,233) valid xyxy 640x360 belongs to Track1 last detection ~45 frames before trigger 267 t=8.9s vs last 222 t=7.4s. Defect temporal mismatch historical bbox + current trigger frame. Scaling 10x/7.5x correct. Cross-job identical RED positions proves stale.

## Before/After
Before: Frame267 red bbox over empty desk.
After: Left Last Detected Frame222 t7.4s full-person bbox Track1 Last Known Position B4 Possible Seat Departure. Right Trigger Frame267 t8.9s Track absent 45 frames NO stale bbox. Fallback single-image uses last-detected frame with Last Known Position at Frame222 Triggered at Frame267. No valid last frame => Last known position unavailable no arbitrary box. B3 same-frame full-person bbox only Track #X B3 Looking Backward Frame N tS Observable event requiring human review.

## Changes
models.py TwoFrameEvidence, rules.py last_known_frame, annotator render_mode trigger/last_detected + _validate_bbox, manager save_two_frame_evidence + filename suffix + buffer handling, service frame_buffer 60, api jobs two_frame_evidence, dashboard migration 2026_09_09_000002 + show.blade.php comparison (col-12 col-lg-6 responsive, zoom, downloads), docs updated.

## DB/API
detection_events: trigger_frame_number, trigger_timestamp, last_detection_frame_number, last_detection_timestamp, last_detection_bbox_json, absence_processed_frames, absence_source_frames_json, bbox_format, processed/source sizes, temporal_status. event_evidence: render_mode, trigger_frame_number, last_detection_frame_number, last_detection_bbox_json, two_frame_evidence_json. API events two_frame_evidence, evidence render_mode.

## Tests 21 pass, 15 regressions: S3/B4 bbox only on last-detected, trigger no stale, frames/timestamps separate, TrackID preserved, B3 same-frame no reuse, missing unavailable, invalid rejected, scaling, deterministic IDs, SHA256, reviews survive, mobile/desktop.

## Existing Data
Non-destructive repair only when both references provable, preserve reviews/audit, else legacy temporal-mismatch recommend rerun.

## Limitations
Old images retain stale boxes need rerun; buffer 60 limit; original 64x48 vs 640x360 scaling stored.

## 2026-09-09 Reliability Extension (Phases 2-10)

- Dedup: EventDeduplicator scoped by (job, source, track, event_type) cooldown 45 frames / 5s, bounded 10k entries, cleanup per job. Preserves first trigger, counts suppressed in metrics. Config: behavior_cooldown_frames.
- Retention: `php artisan evidence:cleanup --dry-run` (default disabled) and `--execute --retention-days=90`. Protected: pending/needs_further_review/confirmed_suspicious, atomic pair preserved, path traversal & symlink rejected, audit logged.
- Immutability: Atomic writes (tmp → replace), SHA256 per image, second hash for last_detected, `php artisan evidence:verify` shows Verified/Not yet verified/Failed/File unavailable. Hash proves bytes match digest, not correctness.
- Missing frames: Explicit reason codes FRAME_NOT_BUFFERED/BUFFER_EXPIRED/CAMERA_DISCONNECTED/FRAME_ENCODING_FAILED/FILE_WRITE_FAILED/FILE_MISSING/HASH_MISMATCH, trigger placeholder zoom disabled, last_detected unavailable message.
- Track lifecycle: generation + expiry 90 frames, prevents reused raw ID inheriting cooldown/bbox/review state, uncertain reassociation starts new lifecycle.
- Fixtures: `ai-service/tests/fixtures/regression_manifest.json` 16 synthetic scenarios, expected observable events language, runner `test_regression_fixtures.py`.
- Review: Existing statuses Pending/Confirmed Observable Event/False Positive/Needs Further Review preserved, audit history immutable, CSRF/transactions enforced.
- Metrics: ProcessingMetric scoped by job, suppressed_duplicate_count etc, pagination preserved.
- Confidence: Labeled Detection Confidence / Rule Score, "Not available" for null, no invented combined score, note "Confidence describes observation, not final determination."
- Security: Path traversal blocked, downloads authorized, notes escaped, deterministic import & SHA256 kept, debug env-controlled.
