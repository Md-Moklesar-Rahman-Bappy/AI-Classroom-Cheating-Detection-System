# SAFE REMOVAL MATRIX
Date: 2026-09-07

| File | Size | Reason | Referenced? | Route Used? | Imported? | Required by Tests? | Required by Docs? | Safe To Delete? | Confidence |
|---|---|---|---|---|---|---|---|---|---|
| query | 7 B | Stray file, no code refs | No | No | No | No | No | YES | 100% |
| ai-service/ai-service/yolo11n.pt | 5.6 MB | Nested duplicate weight, not imported | No | No | No | No | No | YES | 100% |
| ai-service/ai-service/ (dir) | 5.6 MB | Orphan nested package | No | No | No | No | No | YES | 100% |
| .mypy_cache/ | ~25 MB | Python type cache, regeneratable | No | No | No | No | No | YES | 100% |
| .pytest_cache/ + ai-service/.pytest_cache | <1 MB | Test cache | No | No | No | No | No | YES | 100% |
| .ruff_cache/ + ai-service/.ruff_cache | <1 MB | Linter cache | No | No | No | No | No | YES | 100% |
| **/__pycache__/ + *.pyc | ~2 MB | Bytecode cache | No | No | No | No | No | YES | 100% |
| ai-service/pytest_full.log | ~50 KB | Test output log | No | No | No | No | No | YES | 100% |
| dashboard/app/Http/Controllers/VideoAssetController::cleanAbandoned() | — | Dead private method, zero call sites | No | No | No | No | No | YES (method only) | 95% |
| yolo11n.pt (root) OR ai-service/yolo11n.pt (one copy) | 5.6 MB | Duplicate weight; keep ONE | Yes (via settings.model_path) | No | Yes | Partial | No | MAYBE (keep one) | 80% |
| outputs/*_annotated.mp4 (105) | ~100 MB | Generated outputs, gitignored | No (regeneratable) | No | No | No | Thesis evidence | MAYBE | 60% |
| storage/*.mp4 (105) | ~100 MB | Raw uploads, gitignored | No | No | No | No | Thesis | MAYBE | 60% |
| ai-service/storage/*.mp4 (138) | ~120 MB | Duplicate of /storage | No | No | No | No | No | MAYBE (dedupe) | 75% |
| ai-service/outputs/*_annotated.mp4 (138) | ~120 MB | Duplicate of /outputs | No | No | No | No | No | MAYBE (dedupe) | 75% |
| docs/DESIGN_SYSTEM.md + V2.md | <50 KB | Superseded by V3 | No | No | No | No | No | MAYBE (archive) | 70% |
| docs/EVENT_TAXONOMY.md | <20 KB | Superseded by V2 | No | No | No | No | No | MAYBE (archive) | 70% |
| docs/PHASE_2/4/6_TEST_REPORT.md | <30 KB | Old phase reports | No | No | No | No | Historical | MAYBE (archive) | 65% |
| docs/VIDEO_ASSET_*_FIX/TRACE (4 files) | <40 KB | Fix traces | No | No | No | No | Historical | MAYBE (archive) | 65% |
| dashboard/vendor, node_modules | ~140 MB | Build deps | Yes (build) | No | Yes | No | No | NO | 100% Do Not Delete |
| All runtime php/py, migrations, seeders, research/*.md, COMPLETE docx | — | Thesis + runtime critical | Yes | Yes | Yes | Yes | Yes | NO | 100% |

Legend: YES=100% safe, MAYBE=needs thesis review, NO=do not delete.
Total 100% safe savings: ~35 MB. With MAYBE dedupe: +~120 MB.
