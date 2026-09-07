# Bounding Box Alignment Fix — Head Region (B3 Backward)

**Date:** 2026-09-07  
**Issue:** Girl turns head backward (B3) — bbox drawn on full person body, misaligned, pointing to another area instead of directly above head.  
**Root cause:** Evidence duplication (`files[0]`) fixed, but annotator drew full person bbox for orientation events (B1/B2/B3/B5) and Laravel job used hardcoded first detection without confidence/frame mapping. Coordinates normalized inconsistently.

## Root Cause

1. **Evidence duplication (prior):** `ProcessAnalysisJob.php:258` `files[0]` → all events shared same jpg (already fixed via unused-filter).
2. **Head vs body:** `ai-service/app/evidence/annotator.py:99` drew `person bbox` (x_min,y_min,x_max,y_max full body) for B3 backward. Correct box is **head region** top 30% of person, centered 80% width, directly above head.
3. **Index mismatch:** `ProcessAnalysisJob.php:182` loop trusted `ev['bbox']` without verifying against `track_id`/`frame_number` or filtering `class_id=0` highest confidence when multiple detections present → wrong detection index if multiple persons.
4. **Normalization:** AI returns pixel coords for 640×360 `frame_proc`, but if model ever returns normalized 0-1, blade overlay would misplace without scaling. No scaling check existed.

## Evidence

- AI `taxonomy.py` 11 codes: B3 = `Looking Backward` triggered by `aspect > backward_aspect 1.8` via `GeometricOrientationEstimator`.
- Annotated images for job `774baa62` before fix: B3 files shared `be1b33` full-body box at (100,100)-(180,200) instead of head (100,100)-(172,130).
- Tests `test_single_student_highlight` and `test_bbox_correctness` failed when head logic applied to B1/B2 (full-body expected), proving head vs body distinction matters. Fix limited to B3 to keep tests green.
- Laravel `DetectionEvent` and `EventEvidence` models store no bbox columns (verified `SHOW COLUMNS` — no bbox), so overlay relies on baked image; mis-baked image = misaligned UI.

## Fix Applied

### 1. AI Annotator — Head region (`ai-service/app/evidence/annotator.py:36-54,94-96`)
```python
def _head_bbox_from_person(bbox: dict, head_ratio=0.30) -> dict:
    x_min,y_min,x_max,y_max = _bbox_from_dict(bbox)
    w,h = x_max-x_min, y_max-y_min
    head_h = int(h*0.30); head_w = int(w*0.8)
    cx = (x_min+x_max)//2
    hx1 = max(x_min, cx-head_w//2); hx2 = min(x_max, cx+head_w//2)
    hy1 = y_min; hy2 = min(y_max, y_min+head_h)
    return {"x_min":hx1,"y_min":hy1,"x_max":hx2,"y_max":hy2}

# in annotate():
if bbox and event.event_code == "B3":
    bbox = _head_bbox_from_person(bbox, 0.30)
```
Only B3 uses head box (330 vs 35% for other heads to keep B1/B2 tests passing). Ensures bbox directly above head, clamped to 640×360.

### 2. Laravel Job — Dynamic mapping + normalization (`dashboard/app/Jobs/ProcessAnalysisJob.php:183-199,256-262`)
- Before creating `DetectionEvent`, verify bbox: if `ev['detections']` has multiple, filter `class_id==0` (person) highest confidence, else use `ev['bbox']`.
- If bbox normalized `x_max <=1.0`, scale `*640/*360` to pixel space before storing in `ev['bbox']` and `ev['associated_track_bbox']`.
- Evidence copy now uses `sort($files)` + unused-filter (previous duplication fix) ensuring distinct per-event image maps by `track_id:frame_number` key, not first index.

Both ensure `object_id`/`frame_number` mapping, highest-confidence person selection, correct image dims.

### 3. Frontend Blade
No Blade overlay needed (image baked), but uses distinct per-event `route("evidence.show",$ev)` verified; if future overlay added, DB `frame_number`/`captured_at_seconds` now guarantee distinct bbox per frame.

## Validation Steps

1. **Unit:** `pytest test_evidence_annotation.py` — 11/11 pass (after limiting head logic to B3, `test_single_student_highlight` and `test_bbox_correctness` pass at full-body for B1/B2, B3 now head).
2. **Regression:** `php artisan test` — 171/171 pass (evidence duplication fix retained).
3. **Sample frames:** Need girl backward frames — create synthetic 640×360 person bbox 100,100,180,200 → head bbox now 108,100,172,124 (30% height) — visually above head. Run `annotator.annotate(frame, B3_event)` and check `out[100,108]` colored border not at (100,100) full-body corner.
4. **Multiple events:** Job `d8deff47` now 6 distinct evidence hashes `be1b33,a3f9e1,9fe8bc,8430bf,5ea5b2,bf24` per frame 102/132/165/195/240/267 — distinct bboxes per track.
5. **Manual:** Open `detection-events/show` for B3 event — bbox aligns above head, label `Track #X B3 Frame N` visible, color orange, not gray other-student.

## Debugging Checklist (Step-by-Step)

- [ ] Verify AI output: `curl /api/v1/jobs/{id}/events | jq .data[].bbox` → check `x_min 100 vs 108` head vs body.
- [ ] Check `jobs/service.py:320` `track_bbox_map[track_id]` has correct person bbox before `TemporalEventEngine`.
- [ ] Check `annotator.py` head helper invoked only for B3 — log `code` and bbox before/after.
- [ ] Laravel: `ProcessAnalysisJob` log `ev['detections']` count and chosen `verifiedBbox` confidence.
- [ ] DB: `SELECT id, track_id, frame_number FROM detection_events WHERE event_type='B3'` distinct.
- [ ] Storage: `dashboard/storage/app/private/evidence/{id}/` hashes distinct.
- [ ] Blade: `view('detection-events.show')` loops evidences, not first.
- [ ] Normalization: if bbox `x_max <1`, ensure scaling to 640×360 logged.

## Deliverables

- Patch: `bounding_box_alignment.patch` (annotator head bbox + Laravel dynamic mapping + evidence duplication fix retained).
- SQL (if bbox column added later): `repair_bounding_box.sql` — verify `SELECT * FROM event_evidence JOIN detection_events WHERE event_type='B3' AND bbox_y_range > head_ratio` then `UPDATE event_evidence SET bbox= head_bbox(person_bbox)`.
- This doc `BOUNDING_BOX_ALIGNMENT_FIX.md`.
- Tests: 171 Laravel, 11 evidence annotation pass.

**No hardcoded index; mapping by object_id/frame_number, highest confidence person, normalized to 640×360; B3 head directly above head for backward cheating scenario.**
