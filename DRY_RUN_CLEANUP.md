# DRY RUN CLEANUP (Virtual — No Files Deleted)
Date: 2026-09-07

## Current Size
- Measured: 18,708 files, ~266 MB total (includes vendor/node_modules/cache)
- Without vendor/node_modules: ~110 MB primary + ~25 MB cache + ~16 MB weights

## Virtual Deletion Set A: 100% SAFE (Confidence 100%)
| Item | Files | Size |
|---|---|---|
| query | 1 | 7 B |
| ai-service/ai-service/ (nested weight + dir) | 1 | 5.6 MB |
| .mypy_cache/ | ~100 | ~25 MB |
| .pytest_cache (root + ai-service) | ~10 | <1 MB |
| .ruff_cache (root + ai-service) | ~15 | <1 MB |
| **/__pycache__ + *.pyc (all) | ~80 | ~2 MB |
| ai-service/pytest_full.log | 1 | ~50 KB |
| **Total A** | **~208** | **~34 MB** |

Result A: 18,500 files, ~232 MB (-12.8%)

## Virtual Deletion Set B: + MAYBE Dedupe (Needs Review)
| Item | Files | Size |
|---|---|---|
| One yolo duplicate (keep ai-service/yolo11n.pt) | 1 | 5.6 MB |
| Deduplicate videos: remove ai-service/storage + ai-service/outputs duplicates (138+138, keep root 105+105) | 276 | ~120 MB |
| Archive stale docs to audit/ (40 files, kept not deleted) | 0 | 0 (moved) |
| **Total B incremental** | **277** | **~126 MB** |

## Combined A+B
- Files removed: 485
- Directories removed: 5 (.mypy_cache, .pytest_cache x2, .ruff_cache x2, __pycache__ dirs, ai-service/ai-service)
- Size after: ~106-110 MB (if videos deduped) or ~232 MB (safe only)
- Documentation removed: 0 (archived, not deleted)
- Artifacts removed: 105-138 mp4s (if B approved)
- Cache remnants removed: all

## Virtual Cleanup by Category
| Category | Before | After (A) | After (A+B) |
|---|---|---|---|
| Cache | 25 MB | 0 | 0 |
| Duplicate weights | 16.8 MB | 11.2 MB | 5.6 MB |
| Videos | ~220 MB | ~220 MB | ~100 MB |
| Total on disk | 266 MB | 232 MB | 110 MB |

## Risk Assessment
- Set A: zero risk, fully reversible (caches regenerate, stray file irrelevant).
- Set B: requires thesis supervisor sign-off — annotated videos may be evidence for grading.

## No Functionality Broken (Virtual)
- Laravel dashboard: unaffected (no runtime files in A).
- FastAPI: unaffected (detector still loads from remaining yolo copy).
- Tests: unaffected (caches regenerate).
