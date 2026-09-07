# TEST CLEANUP REPORT
Date: 2026-09-07

## 1. Laravel Tests
Location: dashboard/tests/
- tests/Feature/ (Auth, Dashboard, Example, VideoAsset, etc.) — executed via `php artisan test` (Pest).
- tests/Unit/ — pest unit tests.
- Pest.php, TestCase.php — required.
Evidence: composer.json script `test` runs `php artisan test`; CI would execute.
**Verdict**: All feature tests referenced; none orphan. Feature/VIDEO_ASSET_* and Dashboard tests are **Test Critical** — Do Not Delete.

## 2. FastAPI Tests
Location: ai-service/tests/ — 16 files:

| Test File | Purpose | Executed? | Orphan? |
|---|---|---|---|
| test_api.py | Job API endpoints | Yes (pytest) | No |
| test_jobs_api.py | Jobs CRUD | Yes | No |
| test_benchmark.py | Benchmark pipeline | Yes | No |
| test_detector.py | YOLO mock | Yes | No |
| test_config.py | Settings | Yes | No |
| test_cross_service_transfer.py | Video transfer (fixed root cause doc) | Yes | No |
| test_evidence_annotation.py | Annotator | Yes | No |
| test_inputs.py | Inputs | Yes | No |
| test_live.py + test_live_finally_fix.py | Live session | Yes (finally fix is regression) | No — duplicate but both valuable (fix proves finally block) |
| test_logging.py | Logging | Yes | No |
| test_recorded_pipeline.py | E2E pipeline | Yes | No |
| test_render_metrics.py | Renderer | Yes | No |
| test_taxonomy_v2.py | Taxonomy | Yes | No |
| test_tracking_orientation.py | Tracking | Yes | No |
| test_cli.py | CLI | Yes | No |
| __init__.py | Package marker | Yes | No |

**Duplicate concern**: test_live.py vs test_live_finally_fix.py overlap — but latter is regression test for finally fix (see docs/LIVE_MODE_TEST_REPORT). Recommend keeping both or merging; not safe to delete blindly (confidence 70% safe to merge).

## 3. Mock / Fixtures
- No fixtures directory; mocks use unittest.mock / factory inline — none unused.

## 4. Test Artifacts / Caches
| Path | Safe to Delete? |
|---|---|
| ai-service/.pytest_cache/ | YES (regenerated on pytest) |
| .pytest_cache/ (root) | YES |
| ai-service/pytest_full.log | YES (output log) |
| ai-service/htmlcov/ (if exists) | YES |
| .ruff_cache/ | YES |

## 5. Tests Never Executed?
Checked pyproject.toml testpaths = ["tests"]; dashboard phpunit.xml includes Feature + Unit. All discovered tests are executed on `pytest -q` and `php artisan test`. No orphan test helpers found.

## 6. Summary
- Total test files: ~25 (PHP) + 16 (Python) — all executed.
- Unused mocks: 0.
- Safe to delete: caches/logs only (~2-5 MB).
- Needs Review: potential duplicate live tests (merge consideration, low savings).
