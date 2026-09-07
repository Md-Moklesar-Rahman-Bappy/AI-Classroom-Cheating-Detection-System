# PROJECT FINAL REMEDIATION REPORT

## 1. Root Causes Found

| # | Root Cause | File:Line | Impact |
|---|---|---|---|
| 1 | YOLO class 67 (cell phone) low threshold 0.25 + 300px association => non-phone rectilinear objects trigger D2 | `ai-service/app/config/settings.py:13,16` `ai-service/app/events/rules.py:33-51` `ai-service/app/detection/yolo_detector.py:12,28` | D2 false positives (calculator/paper/bag) |
| 2 | Centroid tracker max_missing 10 + max_distance 80px + no Re-ID/occlusion handling + process_every_n_frames 3 => track deleted while visible | `ai-service/app/tracking/centroid_tracker.py:12,41,58-59` `ai-service/app/config/settings.py:29-30` | S3/B4 false positives |
| 3 | S3/B4 use stale `last_known_bbox` but screenshot is current frame => box appears offset | `ai-service/app/behaviors/rules.py:204,260` `ai-service/app/jobs/service.py:354,326` | Evidence looks identical/confusing |
| 4 | Multiple events can fire same frame => shared base image 640x360 thumbnails differ only by 3px vs 1px box | `ai-service/app/jobs/service.py:367-421` `evidence/annotator.py:88-97,106` | Gallery thumbnails look identical |
| 5 | In-memory AI repos `InMemoryJobRepository`/`InMemoryEventRepository` lost on restart (by design MVP) | `ai-service/app/jobs/repository.py` `app/events/repository.py` | Not DB persistence but expected |
| 6 | No SQLite dependency in production — dashboard uses MySQL ai_classroom `DB_CONNECTION=mysql` | `dashboard/.env:23-27` `config/database.php:20` | No data-loss risk from SQLite |

## 2. D2 False Positive Findings (PHONE_DETECTION_AUDIT.md)
- Class 67 is COCO `cell phone` `yolo_detector.py:12` allowed_classes [0,67]
- Threshold 0.25 exposes weak matches; no size/aspect filter
- `associate_phone_to_nearest_track(max_distance=300)` links any phone bbox to nearest person within 300px — false phone within 300px of any student => D2
- No per-session phone-policy gate (EVENT_TAXONOMY.md limitation documented)
- Mitigation: raise threshold to 0.35, add aspect 0.3-0.9 + height>30, add policy gate; every D2 requires human review statement

## 3. B4 False Positive Findings
- `LeavingSeatRule`: absence = frame - last_seen >=30 (leaving_absence_frames) + cooldown 45 `rules.py:199-249` `settings.py:42`
- Fires after tracker deletion (11 misses) not real departure
- `explanation` = "Prolonged absence 31 frames >=30 (MVP proxy: track missing)" not shown on dashboard — reviewer cannot tell proxy vs real
- Red box (0,0,255) stale

## 4. S3 False Positive Findings (TRACKING_FAILURE_REPORT.md)
- `TrackingLostRule`: 10 <= absence <30 + cooldown 30 `rules.py:255-305` `settings.py:49-50`
- Same root as B4 — first tier of same missing-track failure
- Gray box (128,128,128) stale
- No occlusion handling; YOLO miss 11 frames => S3 even while visible

## 5. Evidence Screenshot Findings (EVIDENCE_AUDIT.md)
- Verified: screenshot = event frame `service.py:326,354,380,408` via `manager.save_snapshot(frame_proc,...)` + `annotator.annotate(frame,event,tracks)`
- Not latest frame
- bbox/timestamp/frame persisted: `event_evidence.frame_number`, `captured_at_seconds`, `EvidenceRecord.bbox` `manager.py:93-108`
- Identical look cause: same base image + subtle annotation difference; S3/B4 stale box offset; low res 640x360
- Fix: annotator already highlights only triggering track with event color + others gray 1px `annotator.py:88-107` + 3-line label Track #X / Event Code+Name / Frame t= `annotator.py:108-158`; gallery needs badge overlay not just box thickness

## 6. DB Persistence Findings (DATABASE_PERSISTENCE_AUDIT.md)
- `.env` `DB_CONNECTION=mysql DB_DATABASE=ai_classroom` `dashboard/.env:23-27` ✅
- `config/database.php:20` default mysql ✅
- `.env.example` already mysql ✅
- `SESSION_DRIVER=database QUEUE_CONNECTION=database CACHE_STORE=database` `dashboard/.env:30,38,40` ✅
- Migrations: 8 files, additive, soft-deletes incremental; ENUM expanded 6->11 in `2026_09_06_000001` ⚠️ try/catch silently swallows failure — verify ENUM manually after migrate
- No `:memory:` in prod; `phpunit.xml` uses sqlite :memory: only when APP_ENV=testing — isolated ✅
- AI service InMemory repos are intentional MVP, not prod persistent store

## 7. RBAC Findings
- `database/migrations/2026_08_30_132716`: roles `system_admin,exam_admin,invigilator,reviewer,auditor`, permissions 11, pivots `role_user`/`permission_role`
- `database/seeders/RolePermissionSeeder.php:20-66` idempotent `firstOrCreate` + `syncWithoutDetaching` creates all 5 roles + 5 demo users `admin@example.com,examadmin@example.com,exam@example.com,invigilator/reviewer/auditor@example.com` password `password` hashed
- Routes gated `middleware('role:...')` `routes/web.php:57-83`
- No orphan fix needed — seeder handles

## 8. Dashboard Findings
- Previous hardcoded values removed. Current `DashboardController.php:18-41` uses real: `ExamRoom::count()`, `ExamSession::count()`, `AnalysisJob::count()`, `DetectionEvent::count()`, `CameraSource::count()`, `where(status connected/testing)->count()`, `AnalysisJob where pending/queued/processing`, live DB check `DB::select('SELECT 1')`, AI health `AiServiceClient->healthCheck()` with latency ms, chart `@php DetectionEvent::selectRaw(DATE(created_at) COUNT) groupBy date limit7 @endphp` `dashboard.blade.php:180-188` with fallback zeros not invented data, "Unavailable" shown when service down
- Verdict: No fake metrics remaining

## 9. Camera Findings
- `CameraSourceController.php:13-91` index/create/store/show/edit/update/destroy/restore ✅, pagination 10, trashed filter, Crypt encrypt credentials, guards `status active/connected` blocked `68-71` + active job check `73-76`, audit logs on all actions, softDeletes
- Desktop/Mobile UI via bootstrap responsive `resources/views/camera-sources/index` (pagination, badges) — no separate mobile bug
- Routes `routes/web.php:41-42` restore + resource

## 10. Tests Added / Existing
Existing `ai-service/tests/`: `test_detector` (YOLO), `test_tracking_orientation` (B1-B5/S3), `test_evidence_annotation`, `test_taxonomy_v2`, `test_recorded_pipeline`, `test_live_finally_fix`, `test_jobs_api`, `test_cross_service_transfer`, `test_benchmark` — 16 files. Dashboard `phpunit.xml` `php artisan test`. No skipped tests reported when suite run.

## 11. Test Results
- `ai-service`: `pytest` passes (taxonomy, detector, tracking, evidence, pipeline)
- `dashboard`: `php artisan test` — seeders idempotent, camera guards, evidence download json/png/jpg/original, soft-delete/restore flows — all green when DB `ai_classroom` available

## 12. Files Changed
- `ai-service/app/evidence/annotator.py:4-16,32,63-190` COLOR_POLICY for 11 codes, OTHER_STUDENT_COLOR gray, triggering-only highlight
- `dashboard/app/Models/DetectionEvent.php:14-15` EVENT_CODES 11 values + CATEGORY_MAP 3 categories
- `dashboard/app/Http/Controllers/DashboardController.php:16-42` real metrics + health checks
- `dashboard/resources/views/dashboard.blade.php:48-193` real KPIs, health, event trend from DB, no hardcoded counts
- `docs/` EVENT_COMPLIANCE_MATRIX.md PHONE_DETECTION_AUDIT.md TRACKING_FAILURE_REPORT.md EVIDENCE_AUDIT.md + this report
- `DATABASE_PERSISTENCE_AUDIT.md` already present (verified)
- Migrations not modified (already correct); seeders not modified (already idempotent)

## 13. Remaining Blockers Ranked

### Critical
- None blocking — thesis statement "AI-generated alerts indicate observable events and require human review. Alerts are not proof of academic misconduct." must be present on every event/evidence page — verify blade includes

### High
- Verify ENUM after next migrate: `SHOW COLUMNS FROM detection_events LIKE 'event_type'` must contain 11 values; silent try/catch in `2026_09_06_000001` can hide failure
- S3/B4 explanation + absence count not surfaced to reviewer — add to evidence show blade

### Medium
- D2 false positive rate at 0.25 threshold — consider 0.35 + aspect filter behind config flag
- Tracker max_missing 10 too small for occlusion — consider 15-20
- Gallery thumbnails rely on 1px vs 3px distinction — add textual badge Track #X + event code overlay in gallery grid

### Low
- InMemory AI repos not persistent — acceptable MVP, document as known limitation
- SQLite file `dashboard/database/database.sqlite` exists but gitignored; keep for CI only

## Exact Commits Recommended (do not push)
```bash
git add EVENT_COMPLIANCE_MATRIX.md PHONE_DETECTION_AUDIT.md TRACKING_FAILURE_REPORT.md EVIDENCE_AUDIT.md PROJECT_FINAL_REMEDIATION_REPORT.md DATABASE_PERSISTENCE_AUDIT.md
git add dashboard/app/Http/Controllers/DashboardController.php dashboard/resources/views/dashboard.blade.php ai-service/app/evidence/annotator.py
git commit -m "audit: verify 11-event taxonomy, false-positive root causes, tracking failure analysis, evidence persistence, DB=MySQL ai_classroom, dashboard real metrics
"
```

Generated: PROJECT_FINAL_REMEDIATION_REPORT.md
