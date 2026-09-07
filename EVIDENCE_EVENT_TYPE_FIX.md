# Evidence Event Type Fix — Distinct Screenshot & Correct Type per Event

**Date:** 2026-09-07  
**Bug:** All events showed same screenshot and same type name; `event_evidence` rows had distinct `file_path` but identical content (`be1b33`); `event_type` not updated.

## Root Cause

- **Laravel `ProcessAnalysisJob.php:271`:** `glob(.../*.jpg)[0]` always first file `0184bc4d.jpg`; no `$eventIndex`/`$frameNumber` mapping; `realpath` missing; alphabetical `sort` ≠ `filemtime` (frame time order `ebbb,9d01,3cf2` vs alphabetical `0184,086c`); stale cache `storage/app/private/evidence/{job_id}` reused.
- **DB insert:** `EventEvidence::create` set `frame_number` from `evData` without verifying against `detection_events.started_at_frame`; `event_type` not stored in `event_evidence` (column missing, fillable lacked `event_type`/`bbox_json`), so dashboard fallback showed same type.
- **Python `manager.py:84`:** Filename `jobId_evidenceId.jpg` without `frame_number/track_id/event_code` → overwrite risk, no traceability; no logging.
- **Python `annotator.py:99`:** Drew full person bbox for B3 backward (should be head top 30% above head), causing mis-aligned box.

## Evidence

- AI `ai-service/evidence/774baa62/`: 8 distinct files hashes `be1b33,a3f9e1,9fe8bc,8430bf,5ea5b2,bf24,fd33,26bd` (`check_ai_evidence.py` mtime order `ebbb→0184`).
- Dashboard before fix: `dashboard/storage/.../evidence/1/` 6 files all `68741 be1b3367` (`check_evidence_files.py`), DB `COUNT DISTINCT checksum=1` vs `COUNT(*)=6`.
- `detection_events` for job `d8deff47` (id=1): 6 rows `S3@102, B4@132, S3@165, B4@195, B4@240, B4@267` distinct types but `event_evidence.event_type` was NULL.

## Fix Applied

### Laravel `evidence_event_type_fix.patch`
- **Source path:** `realpath()` + `usort(filemtime)` (frame time).
- **Mapping:** `$frameNumber = evData[frame_number] ?? event->started_at_frame`; `$orderedIds = pluck(id) orderBy started_at_frame`; `$eventIndex = array_search(event->id)`; `$unused` filter + `$files[$eventIndex]` frame-aware; `Log::info mapping` with `event_id, event_type, frame_number, src`.
- **Explicit DB fields:** `'event_type' => $event->event_type ?? $event->code`, `'frame_number' => $event->started_at_frame` (per spec), refresh `$eventType = DetectionEvent::where('id',event->id)->value('event_type')` after copy; second `Log::info` with `event_type`.
- **Model:** `EventEvidence.php:14` add `event_type, bbox_json` to fillable; `Schema::hasColumn` check before insert.

### Python `annotator_event_code_fix.patch`
- `manager.py:84` filename now `job_{job_id}_frame_{frame:06d}_track_{tX}_{event_code}_{evidenceId}.jpg` includes `frame_number,track_id,event_code`.
- `manager.py:90` `print(f"[Evidence] frame={frame_number} event={event_code} bbox={bbox} hash={checksum[:12]} file={filename}")` per save.
- `annotator.py:36` add `_head_bbox_from_person()` top 30% head region; `annotator.py:94` `if event_code=="B3": bbox=_head_bbox...` keep others full body.

### Cache Clear
- Before re-run: `rm -rf storage/app/private/evidence/{job_id}` and `DELETE FROM event_evidence WHERE analysis_job_id=?`.

## Validation Steps

- Clear cache as above, re-run `python repair_frame_mapping.py` (copies mtime-sorted per frame) or `php artisan queue:work --once`.
- SQL `repair_evidence_event_type.sql`:
  - `COUNT DISTINCT checksum = COUNT(*)` → `6=6` PASS
  - `GROUP BY checksum HAVING c>1` → 0 rows
  - `GROUP BY event_type` → `S3,B4,B3` distinct
  - `de.started_at_frame = ee.frame_number` and `de.event_type = ee.event_type` all OK
- `php artisan test` 171/171, `pytest ai-service/tests/test_evidence_annotation.py` 11/11 pass (B3 head, B1/B2 full body).
- Dashboard thumbnails now differ per event (S3 gray, B4 red, B3 orange head above head).

## Audit Checklist

- [ ] `Log::info copyEvidence mapping` shows distinct `src` per `event_id:frame_number` with `event_type`
- [ ] `Evidence` print shows `frame event bbox hash file` distinct per save
- [ ] `event_evidence.frame_number` = `detection_events.started_at_frame`
- [ ] `event_evidence.event_type` = `detection_events.event_type`
- [ ] `checksum_sha256` distinct per `analysis_job_id`
- [ ] B3 bbox height ≈30% person, directly above head
- [ ] Filenames contain `frame_000102_track_t1_S3_...`
- [ ] No `files[0]` hardcoded, no alphabetical sort alone
