# CLEANUP REPORT
Branch: cleanup/production-readiness (from f18907f)
Base preserved: main (84cbefb)
Audit branch: audit/full-production-readiness
Status: CONTROLLED PILOT / PRODUCTION CANDIDATE

## Safety Confirmation
- No destructive reset, clean, broad checkout, or force-push.
- .env and credentials never committed.
- No migrate:fresh / db:wipe executed.
- Quarantine used (C:\temp\cleanup_quarantine) before permanent removal.
- Cleanup inventory created (CLEANUP_INVENTORY.md).

## Files Removed (evidence from git status)
- .ruff_cache (root)
- .pytest_cache (ai-service/)
- debug_bbox.py, debug_bbox_pipeline.py, check_ai_evidence.py, check_evidence_files.py, check_job5.py, fix_tests.py, fix_tests2.py, gate4_yolo_smoke.py, gate5_synthetic_video.py, gate6_benchmark.py, query_events.py, query_events2.py, query_events3.py, query_events4.py, repair_evidence.py
- repair_evidence_duplication.sql
- annotator_bbox_fix.patch, annotator_event_code_fix.patch, bounding_box_head_fix.patch, bounding_box_laravel_fix.patch, evidence_duplication_fix.patch, evidence_event_type_fix.patch, evidence_frame_mapping_fix.patch
- bbox_debug_comparison.jpg, bbox_debug_overlay.jpg
- audit_docx.py, audit_v2.py, generate_docx.py, generate_v2.py, verify_docx2.py, verify_gate2.php, repair_frame_mapping.py, repair_temporal_evidence.py
- files.txt
- Nested duplicate ai-service/ai-service/yolo11n.pt and empty nested dir

## Directories Removed
- .ruff_cache (root)
- .pytest_cache (ai-service/)
- ai-service/ai-service (duplicate model folder)

## Dependencies / Artifacts Removed
- None from composer.json/package.json/requirements.txt (only temporary files/scripts removed; no dependency entries changed)
- Quarantine contains removed files for recovery

## Code Removed
- Development/debug scripts (no registered controllers/services/routes/tests reference them)
- Repair SQL/patch files (no active migration references)
- Temporary audit/generation scripts

## Retained Despite Appearing Obsolete
- All audit reports preserved as history; excluded from release via manifest rules
- ai_classroom.sql / ai_classroom1.sql preserved (existing dumps); excluded from release
- Original DOCX source preserved
- All migrations, controllers, services, tests, views preserved
- .env.audit preserved temporarily; not committed

## Verification Performed
- composer validate --strict: PASS
- php artisan about: PASS (Laravel 12.68, PHP 8.2.12)
- php artisan route:list: PASS
- php artisan migrate:status: PASS (no unexpected pending)
- php artisan test --testsuite=Unit: PASS
- php artisan test --filter=Authentication: PASS (8 passed)
- Maintenance branch created; main unchanged
- No syntax breaks from removal (debug files unreferenced)

## Remaining Findings (honest)
- FastAPI smoke: BLOCKED (model load timeout; not cleanup regression)
- Frontend build: FAIL (pre-existing Tailwind/PostCSS version mismatch)
- Pint: FAIL (pre-existing 29 files)
- PHPStan: NOT EXECUTED (missing)
- Black: FAIL (pre-existing)
- Dependency audits: NOT EXECUTED
- Queue benchmark: NOT EXECUTED
- Synthetic integration: NOT EXECUTED
- Database restore fully: NOT EXECUTED (disposable DB used; persistent restore planned)
- Last-admin concurrency test: NOT EXECUTED (design verified)
- Private-storage tests: NOT EXECUTED
- Security-center view: NOT CREATED (controller/auth verified)

## Rollback Procedure
1. Check out removed files from quarantine (C:\temp\cleanup_quarantine) or restore from git using commit f18907f.
2. Restore branch from main if needed.
3. Rebuild artifacts (npm ci; build fix required separately).
4. No database changes required from cleanup.

## Final Readiness Classification
CONTROLLED PILOT / PRODUCTION CANDIDATE — not PRODUCTION READY.
