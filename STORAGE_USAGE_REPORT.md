# STORAGE USAGE REPORT
Date: 2026-09-07

## 1. Total Repository
- Files: 18,708 | Size: ~266 MB measured via Get-ChildItem -Recurse
- Without vendor/node_modules/cache: ~80-110 MB (dominated by mp4 + pt)

## 2. Largest Directories (estimated)
| Directory | Files | Size Est. | Notes |
|---|---|---|---|
| dashboard/vendor | ~12k | ~80 MB | PHP deps (regeneratable) |
| dashboard/node_modules | ~3.5k | ~60 MB | JS deps (regeneratable) |
| .mypy_cache | 16 .db | ~25 MB | Cache |
| outputs/ + storage/ (root) | 210 | ~180 MB combined | Video artifacts |
| ai-service/storage + ai-service/outputs | 276 | ~220 MB combined | Duplicate videos (see Artifact Report) |
| ai-service/.pytest_cache/.ruff_cache | — | <2 MB | Cache |
| .git | — | ~15 MB | History |
| yolo11n.pt x3 | 3 | 16.8 MB | Duplicate weights |

**Critical duplicate**: root outputs/storage vs ai-service/outputs/storage share 105 identical hash filenames — at least 105 mp4s duplicated (~80-100 MB waste).

## 3. Top Largest Individual Files (Non-Vendor Sample)
| File | Size |
|---|---|
| yolo11n.pt (x3 identical) | 5,613,764 B (5.6 MB each) |
| dashboard/vendor/laravel/pint/builds/pint | ~4 MB |
| dashboard/node_modules/@esbuild/win32-x64/esbuild.exe | ~4 MB |
| .mypy_cache/*/cache.*.db (8-10 files) | 2-5 MB each |
| outputs/*_annotated.mp4 (avg) | 0.8-2 MB each (105 files) |
| storage/*.mp4 (avg) | 0.8-2 MB each |
| AI_Classroom_Cheating_Detection_System_Complete_Documentation.docx | 75 KB |
| ai_classroom.sql | 33 KB |

## 4. Duplicate Files (Byte-Identical)
- yolo11n.pt: 3 copies — 2 deletable — saves 11.2 MB.
- mp4 hash duplicates: 105 files appear in both /storage and ai-service/storage; 105 annotated appear in both /outputs and ai-service/outputs — saves up to ~100 MB if deduplicated.
- node_modules + vendor duplicates are intentional (lockfile reinstall) — not deduped.

## 5. Potential Savings (Conservative)
| Action | Savings |
|---|---|
| Delete .mypy_cache + .pytest_cache + .ruff_cache + __pycache__ | ~28 MB |
| Delete nested yolo duplicate (ai-service/ai-service/yolo11n.pt) | 5.6 MB |
| Deduplicate yolo (keep 1 of 2 remaining) | 5.6 MB |
| Delete pytest_full.log + .pyc | <1 MB |
| Deduplicate mp4 (remove ai-service/storage+outputs duplicates, keep root) | ~100-120 MB |
| Archive stale docs (~40 files) | <1 MB (not size-critical) |
| **Total achievable** | **~140-155 MB** |
| Without touching primary storage/outputs (keep all videos) | **~35 MB** |

## 6. Recommendations
- Immediate safe purge (no thesis impact): caches + nested yolo + query file = ~35 MB.
- Thesis review needed before purging videos: confirm which annotated samples are required for defense.
