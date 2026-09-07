# ARTIFACT AUDIT REPORT
Date: 2026-09-07

## Scope
outputs/, storage/, ai-service/outputs, ai-service/storage, evidence/, .mypy_cache, etc.

## 1. Video Artifacts (Gitignored, Regeneratable)
| Location | Count | Est. Size | Duplicate? | Regeneratable? | Safe to Delete? |
|---|---|---|---|---|---|
| /outputs/*_annotated.mp4 | 105 | ~80-120 MB | Partial (105 names overlap ai-service/outputs) | Yes (re-run AI pipeline via POST /api/v1/jobs/recorded) | YES, if thesis evidence preserved |
| /storage/*.mp4 | 105 | ~80-120 MB | Yes (subset of ai-service/storage 138) | No (originals) but gitignored | MAYBE — keep if originals needed |
| ai-service/outputs/*_annotated.mp4 | 138 | ~100-150 MB | Superset of /outputs | Yes | YES (deduplicate) |
| ai-service/storage/*.mp4 | 138 | ~100-150 MB | Superset of /storage | Originals | MAYBE |
| ai-service/outputs/evidence_samples/ | ? | small | Evidence clips | Yes | YES |

**Duplication proof**: hash filenames identical across directories (e.g., 3f9da2b1412a42989108565ae6ca2fc8.mp4 appears in all 4 locations). Recommend canonicalize to `/storage` + `/outputs` only and remove ai-service/* copies (or vice versa).

## 2. Model Weights
| File | Size | Status |
|---|---|---|
| yolo11n.pt (root) | 5.61 MB | Canonical |
| ai-service/yolo11n.pt | 5.61 MB | Duplicate |
| ai-service/ai-service/yolo11n.pt | 5.61 MB | Nested duplicate (orphan) |

All three byte-identical. Keep ONE (ai-service/yolo11n.pt per Docker convention) and delete rest. Total savings: 11.2 MB if 2 removed.

## 3. Evidence Directories
- evidence/ (root): empty, gitignored — safe to keep as placeholder (.gitkeep would be better) — 0 bytes.
- ai-service/evidence/: empty — same.

## 4. Cache / Temporary
| Path | Files | Size Est. | Safe? |
|---|---|---|---|
| .mypy_cache/ (16 .db files) | 16 | ~20-30 MB | YES (regeneratable via mypy) |
| .pytest_cache/ | ~5 | <1 MB | YES |
| .ruff_cache/ | ~10 | <1 MB | YES |
| dashboard/storage/logs/, framework/cache, bootstrap/cache | — | <5 MB | YES (cleared via artisan optimize:clear) |
| scripts/__pycache__, ai-service/**/__pycache__, .pyc | ~73 .pyc | <2 MB | YES |
| ai-service/pytest_full.log | 1 | unknown | YES (test output) |

## 5. Benchmark / Research Results
- research/results/benchmark_results.json, low_resource_profile.json, sanitized_evaluation_result.json — small (<100KB), required for reproducibility — **Do Not Delete**.
- research/experiments/*.json, manifests/MANIFEST.json — required.

## 6. Thesis Artifact
- AI_Classroom_Cheating_Detection_System_Complete_Documentation.docx (75KB) — deliverable — **Do Not Delete**.

## 7. Recommendations
- SAFE TO DELETE NOW: .mypy_cache, .pytest_cache, .ruff_cache, **/__pycache__, *.pyc, pytest_full.log, nested yolo duplicate (5.6 MB).
- DEDUPLICATE: Keep either /outputs+storage OR ai-service/outputs+storage, not both — saves ~100+ MB.
- COMPRESS: Move thesis-critical annotated videos to cold storage before purge; keep 10-20 samples for demo.
