# Behavior Event Limitations

## MVP Scope
Phase 4 implements 5 temporal rules with documented proxies; not a production cheating determination.

## Leaving Seat - Partially Implemented
**MVP definition used:** Prolonged absence from established track region (`LeavingSeatRule` with `leaving_absence_frames=30`).

- **Not** true seat assignment: no manual ROI/seat map configured; no calibration of desk coordinates.
- **Not** assuming leaving camera view = leaving seat: person may leave view but remain seated outside camera frustum - our proxy would incorrectly emit if track missing due to occlusion or detector miss.
- **Documented limitation:** If reliable seat-region method (manual polygon per seat, or track region vs seat region IoU) cannot be implemented in Phase 4, feature is marked **partially implemented**. Current proxy uses `track missing >=30 frames` (~3s at 10fps) as observable departure from track region.
- **Alternative documented proxies:**
  - Departure from manually configured seat region (requires UI to draw seat polygons, not implemented)
  - Prolonged absence from established track region (implemented)
  - Another proxy: bounding box centroid moving outside initial track's 1.5x expanded ROI (not implemented)

**Mitigation:** Event explanation states `"MVP proxy: track missing"`; `requires_review=True`; visualization red but never "cheater". Human must verify via evidence snapshot and full video.

## Orientation Approximation
- Geometric method coarse: delta <0.15 width = forward, not subtle gaze; may miss small head turns; quality `low` for small bboxes.
- Backward via aspect ratio (h/w >1.8) is not true yaw; standing person may trigger false backward. Threshold configurable but still proxy.
- No landmarks: `visible_landmark_count=0`, so occlusion not directly measured; `uncertain` fallback used.

## Brief Movement
- Brief left/right (2 frames) does not emit repeated event because `min_supporting 8, min_duration 10` require sustained observation. Single-frame noise suppressed via temporal window and missing tolerance.

## Duplicate & Cooldown
- Cooldown 45 frames prevents repeated alerts for same track; may suppress true repeated events if they occur within 4.5s. Configurable.

## Track Continuity
- SimpleCentroidTracker: ID switch if persons cross within 80px or occluded >10 frames; may split one person's repeated left into two separate buffers, missing event.
- Reappearance within 10 frames reuses ID; beyond that new ID, event buffer resets (insufficient evidence).

## Insufficient Evidence
- State `uncertain/unavailable` or quality `low` yields gray visualization, no red event. If camera far (small bboxes), many observations will be low quality -> system will report "insufficient evidence" rather than false negative. This is intentional.

## No Facial Recognition
- No face mesh, no identity, no emotion/intention inference. Tracks are anonymous session IDs, not persisted.

## Evidence Selection
- Best representative frame: current frame where event first met (window end), not every frame. Optional short clip not yet implemented (would require frame buffering). Evidence stores `event_id` link, timestamps, config version, method version.

## Evaluation Fixtures
Synthetic bbox shifts (not real video) used for unit tests; staged classroom recordings needed for real-world validation before accuracy claims. No FACs/FPS claims made; actual CPU measured <1ms tracker/orientation + 1.29s YOLO detection per frame.

## Privacy
- No biometric storage; track IDs temporary; observations buffered 15 frames then pruned.

## Future Work
- Manual seat ROI UI, MediaPipe/YOLO pose if FPS headroom verified, ByteTrack benchmark.
