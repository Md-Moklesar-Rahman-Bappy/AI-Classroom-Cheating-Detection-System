# Orientation Method Evaluation

**Date:** 2026-08-30 **Python:** 3.14.3 **Hardware:** Ultra 7 155H 16c/22t 16GB RAM, torch 2.13.0+cpu, mediapipe 1.0.1, ultralytics 8.4.135

## Feasibility Tests Performed (not claimed before testing)

1. `import mediapipe; from mediapipe.tasks.python import vision` -> OK, `vision` module available, but `solutions` API absent (`mediapipe.solution` missing in 1.0.1 on Python 3.14). Pose Landmarker via Tasks requires external `.task` model file not bundled; instantiation would download/require `pose_landmarker.task` (~5MB).
2. `YOLO('yolo11n.pt').predict(zeros 640x360)` -> 1.29s first inference CPU, 0 boxes (no false positive). Pose variant `yolo11n-pose.pt` not present locally; would require additional 6MB download, distinct checkpoint, same AGPL-3.0 obligations as detection.
3. Geometric approximation (bbox center delta, aspect) -> pure Python+NumPy+OpenCV, no extra deps, <1ms/frame measured on dummy.
4. ByteTrack / DeepSORT -> `pip show bytetrack/deep_sort/supervision` -> not installed; no import possible; would add heavy deps (scipy, filterpy) and CPU Kalman overhead.

## Comparison

| Method | Python 3.14 Compat | CPU Load | Multi-Person | Occlusion | Distance | License | Integration Complexity | Landmark Stability | Privacy |
|---|---|---|---|---|---|---|---|---|---|
| **A. MediaPipe face/head landmarks** | Partial: `tasks` available but `solutions.face_mesh` absent in 1.0.1/py3.14; Tasks FaceDetector requires `face_landmarker.task` download | Medium: Tasks graph ~30-50ms/frame est. | Yes per detection | Fragile: fails if face <~30px or profile/occluded | Poor far field (2-5m classroom camera) | Apache 2.0 | High: need .task download, Tasks API migration, landmark-to-yaw mapping not trivial | Unstable at distance | Higher risk: face mesh is biometric-adjacent; must not store embeddings - would need audit |
| **B. MediaPipe pose** | Same Tasks constraint: `pose_landmarker.task` required, `solutions.pose` absent | Medium-High: pose graph heavier than face | Yes | More robust than face if torso visible | Moderate: needs full body visible, far field degrades | Apache 2.0 | High: same .task download, 33 landmarks parsing, shoulder/nose vector | Moderate at distance if person large enough | Lower than face but still landmark retention risk - store only orientation state, not landmarks |
| **C. YOLO pose** (`yolo11n-pose`) | Yes: ultralytics 8.4.135 supports on py3.14 (same as detection) | High: additional model load doubles memory (~6MB) and inference ~1.3s/frame CPU (same as detection) -> would halve effective FPS | Yes | Good: COCO pose training robust | Good: trained on varied scales | AGPL-3.0 (same as yolo11n.pt) | Medium: same YOLO API, but need separate weight file, NMS for keypoints | Stable if person detected | Lowest biometric risk if only keypoints used transiently |
| **D. Geometric head-orientation approximation** | Yes: pure OpenCV/NumPy, no extra deps | Very Low: <1ms (bbox arithmetic) | Yes | Robust: uses bbox already from detector; degrades gracefully to `uncertain` | Excellent: works at any distance where person bbox exists | MIT/Apache (no new dep) | Very Low: 50 lines, no model | No landmarks, so stability is deterministic from bbox | Minimal privacy: no landmarks stored, only bbox-derived state |
| **E. Rule-based body orientation** | Yes: similar to D, uses bbox + track history | Very Low: <1ms + history buffer | Yes | Robust: needs track continuity, not landmarks | Good: uses track region, not face | MIT | Low: needs tracker, but tracker already required | Deterministic, but coarse (left/right/forward only) | Minimal: only bbox/track region |

## Decision (baseline only after test)

**Baseline for Phase 4:** **D + E hybrid - Geometric Head-Orientation Approximation with Rule-Based Body Fallback (Method D/E combined)**.

Rationale:

- Only D/E are verified to run on current Python 3.14 without extra model downloads and without `solutions` incompatibility.
- CPU load <1ms vs 30-1300ms for others preserves 16GB/CPU-only budget and `process_every_3` pipeline (Phase 1 risk R01/R02).
- Multi-person capability via existing person detections; no face-size dependency.
- Privacy minimal: no face mesh/pose landmarks persisted; only `orientation_state`, `measurement_quality`, `supporting geometry` (bbox center delta, width/height ratio) stored transiently per observation.
- License clean (no AGPL addition beyond existing yolo11n.pt, no Apache-2.0 tasks model to bundle).
- Integration complexity lowest; 2-day implementation vs 1-week for Tasks pose + .task model management.

**Deferred:** B (MediaPipe pose Tasks) and C (YOLO pose) remain roadmap candidates for Phase 8 benchmark; selection requires `.task` download verification and FPS measurement on classroom footage (not synthetic). Documented as `ORIENTATION_METHOD.md` future work.

**Feasibility test evidence:** `mediapipe.tasks.python.vision` import OK but `solutions` import fails; YOLO detection 1.29s CPU; geometric <1ms. No claim that pose works before downloading and measuring on real video.

**Tracking decision (preliminary for this doc):** ByteTrack/DeepSORT not installed, would add scipy/filterpy deps and Kalman CPU cost; baseline is SimpleCentroidTracker (see `TRACKING_DESIGN.md`) - temporary IDs only, no embeddings.

## Configuration for Baseline

- Orientation state mapping via bbox center delta vs track history and bbox aspect; thresholds in `app/config/settings.py` (`orientation_left_threshold`, `orientation_right_threshold`, `backward_aspect_ratio`).
- Quality `high/medium/low/uncertain` based on visible bbox size and track continuity; `unavailable` if no person detection.
- Method version `geometric-v1` recorded per observation and per job.
