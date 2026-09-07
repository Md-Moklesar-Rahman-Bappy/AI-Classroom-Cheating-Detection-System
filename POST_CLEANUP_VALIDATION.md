# POST CLEANUP VALIDATION (Pre-Execution — Dry Run)

## Status: NO FILES DELETED YET (Audit Phase Only)
This report defines validation gates for Phase 11 optional cleanup.

## 1. Pre-Cleanup Baseline
- Total files: 18,708 | Size: ~266 MB
- Git status: clean (except ignored artifacts)
- Tests: not yet run in this audit (validation pending)

## 2. Validation Gates (To Run After SAFE Deletions)
| Gate | Command | Expected |
|---|---|---|
| FastAPI tests | `pytest -q` in ai-service/ | All 16 suites pass |
| Laravel tests | `php artisan test` in dashboard/ | Pest passes |
| AI health | `GET /api/v1/health` | 200 {status: ok} |
| Dashboard health | `GET /health/ai` | 200 ai_service: ok |
| Detector load | Check logs "Detector loaded" | Model from remaining yolo copy |
| Evidence pipeline | POST /api/v1/jobs/recorded sample | Output + events returned |
| Docs build | `python scripts/generate_complete_documentation.py --dry-run` | No error |

## 3. Rollback Plan
- All SAFE deletions are regeneratable: `mypy`, `pytest`, `pip install`, `npm install` restore caches.
- Duplicate yolo can be restored via `copy ai-service/yolo11n.pt yolo11n.pt`.
- No git-tracked runtime files deleted in SAFE set, so `git checkout -- .` restores if needed.

## 4. Post-Cleanup Size Target
- SAFE only: ~232 MB (-34 MB, -12.8%)
- With dedupe (if approved): ~110 MB (-156 MB, -58%)

## 5. Recommendation
Execute SAFE deletions now, run gates, then submit results as addendum to this report.
