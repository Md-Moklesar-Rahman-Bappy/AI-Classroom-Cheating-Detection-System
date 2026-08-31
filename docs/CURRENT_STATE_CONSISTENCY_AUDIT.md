# Current State Consistency Audit

**Date:** 2026-08-31  
**Commit Baseline:** `e76a9689b141a3828e89c1588971a4b7aa83fe1c` (main)  
**Previous Document Commit:** `30a6ba2`  
**Auditor:** Principal Engineer (automated audit + manual verification)

## 1. Program Name

| Source | Claim |
|---|---|
| `scripts/generate_complete_documentation.py:208` | `Master's in Computer Science and Engineering` |
| Historical Word cover (per mission brief) | `Master's in Information Technology` |
| Repository-approved material | `scripts/generate_complete_documentation.py` is authoritative |

**Finding:** Inconsistency exists in historical material. Current script correctly states **CSE**. Cover currently generates CSE. No IT claim found in current code after Phase 0 fix.

**Action:** Preserve CSE. Word already corrected. Mark IT as superseded.

## 2. Repository Commit

| Source | Value |
|---|---|
| Word doc footer (scripts/generate_complete_documentation.py:215,251) | `30a6ba2` |
| Documentation reports (COMPLETE_DOCUMENTATION_QA_REPORT etc.) | `e76a968` |
| Actual `git rev-parse HEAD` | `e76a9689b141a3828e89c1588971a4b7aa83fe1c` |

**Finding:** Word generator still embeds `30a6ba2` hard-coded. All docs should reference `e76a968` as new baseline after this upgrade (to be updated to final commit).

**Action:** Update generator to use dynamic commit or bump to new commit after this upgrade. Fixed in this audit cycle.

## 3. Model Checksum

- **Actual file:** `yolo11n.pt` SHA-256 = `0ebbc80d4a7680d14987a577cd21342b65ecfd94632bd9a8da63ae6417644ee1` (verified via `php -r hash_file`)
- **Correct in code:** `ai-service/app/detection/yolo_detector.py` uses runtime checksum; `benchmark_manifest.json` correct
- **Incorrect in docs:** `scripts/generate_complete_documentation.py:479` has typo `...ae4617644ee1` (missing `63` → `46` vs `64`). Should be `...ae6417644ee1`
- **Also incorrect:** `docs/API_CONTRACT.md` uses placeholder `abc...`

**Action:** Fix generator checksum string. Fix API_CONTRACT placeholder. Ensure all docs use verified value.

## 4. YOLO Class ID

- **Actual COCO mapping (ultralytics YOLO11n):** `67 = cell phone` (verified via `YOLO('yolo11n.pt').names`)
- **Correct in code:** `ai-service/app/config/settings.py: allowed_classes=[0,67]`, `yolo_detector.py: COCO_NAMES={0:"person",67:"cell phone"}`, `events/rules.py: class_id==67`, `rendering/renderer.py: 67`
- **Incorrect in docs:** `docs/DATA_PLAN.md:40` says 66, `docs/EVENT_TAXONOMY.md:15` says 66, `docs/FINAL_PROJECT_STATUS.md:9` says 66, `research/ANNOTATION_GUIDE.md:15` says 66, `scripts/generate_complete_documentation.py:479` says 66, `docs/MODEL_CARD.md` ambiguous

**Finding:** Systematic documentation drift: 66 vs 67.

**Action:** Correct all docs to 67. Add comment in code linking to COCO verification.

## 5. Hardware

| Source | Environment |
|---|---|
| `docs/BENCHMARK_REPORT.md` (historical) | `Intel i5-14500, 8 GB RAM, no GPU, CPU-only` |
| `research/results/benchmark_results.json` + later reports | `Core Ultra 7 155H, 16 GB RAM, NVIDIA (when available)` |
| Current audit host (win32) | `Windows 11, XAMPP PHP 8.2.12, Node 22.20.0, Python 3.14.0` |

**Finding:** Two distinct benchmark environments legitimately exist. Must not merge. Each report must label its host.

**Action:** Record separately in `docs/BENCHMARK_REPORT.md` with host field.

## 6. Test and Quality Status

| Check | Docs claim (old Word Part X) | Actual Phase history | Current re-run (2026-08-31) |
|---|---|---|---|
| Laravel tests | "Not executed" | Passed in Phase 4/6 reports | **147 passed** (pest 3.8.7) |
| Python tests | "No dedicated folder" | Mixed | 6/6 passed for detector/config subset; full suite needs python-multipart (now installed) but heavy model load causes timeout in CI-less env |
| Ruff | "Not executed" | Not run | To be run |
| Black | "Not executed" | Not run | To be run |
| Pint | "Not executed" | Not run | To be run |
| Composer validate | "Not executed" | Not run | To be run |
| npm build | "Not executed" | Not run | To be run |

**Action:** Update all docs to reflect actual current evidence (not historical "not run").

## 7. API Paths

| Docs (abbreviated) | Actual FastAPI routes (verified) |
|---|---|
| `POST /jobs` | `POST /api/v1/jobs/recorded` |
| `GET /jobs/{id}` | `GET /api/v1/jobs/{id}` |
| `GET /health` | `GET /api/v1/health` |
| `GET /version` | `GET /api/v1/version` |
| `POST /live/start` | `POST /api/v1/live/start` |

**Finding:** Docs in Part V use abbreviated `/jobs` without `/api/v1` prefix.

**Action:** Correct `docs/API_CONTRACT.md` to use full prefix.

## 8. Live States

- **Actual enum (ai-service/app/api/live.py, live/service.py):** `monitoring`, `connected`, `reconnecting`, `stopped`, `degraded`
- **No `paused` state** implemented.
- **Dashboard `live/index.blade.php`:** correctly shows standby/monitoring.

**Action:** Ensure no docs invent `paused`.

## 9. Color Semantics

| Context | Mapping (verified) |
|---|---|
| `ai-service/app/rendering/renderer.py` | `person (0) → green #16A34A`, `cell phone (67) → blue #2563EB` |
| `docs/EVENT_TAXONOMY.md` | States blue visual state for D2 |
| Dashboard badges | `B* → red`, `D2 → blue`, `D1 → green` |
| Consistent? | **Yes**, but docs Part IV section 66 mixes: "Green normal, Amber uncertain, Red suspicious, Blue evidence, Gray inactive" — needs alignment with renderer |

**Action:** Unify legend in `docs/DESIGN_SYSTEM_V3.md` to match renderer.

## 10. Project Status

| Category | Status |
|---|---|
| Implemented & runtime validated | Recorded video workflow, VideoAsset CRUD, AnalysisJob lifecycle, Events/Evidence/Review, Audit logs |
| Implemented & tested (synthetic) | Anonymous tracking, orientation-v1, temporal rules |
| Partial / experimental | B4 Possible Seat Departure, orientation accuracy, live RTSP |
| Blocked | Real participant dataset (consent-gated) |
| Planned | GPU benchmark, EZVIZ verification, multi-source correlation |

**Finding:** Docs generally preserve distinction correctly (Part I sections 8.x). No mixing of synthetic benchmark as real-world accuracy.

**Action:** Keep distinction; ensure FINAL_PROJECT_STATUS reflects current test evidence.

---

## Summary Actions for This Upgrade

1. Fix generator checksum typo (479)
2. Fix all YOLO class 66 → 67 in docs
3. Bump Word commit from 30a6ba2 → actual HEAD (dynamic)
4. Correct API paths to `/api/v1/...`
5. Record hardware environments separately
6. Update quality status from "not run" to actual results
7. Unify color legend
8. Regenerate DOCX after fixes

All fixes tracked in `docs/FULL_APPLICATION_AUDIT.md` and applied in this commit.
