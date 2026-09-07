# Evidence Pipeline Fix — Distinct Screenshot & Type per Event

**Date:** 2026-09-07  
**Bugs:** 1) All `event_evidence` rows pointed to same file content (duplicate `files[0]` + alphabetical vs mtime + stale cache). 2) All events showed same type name due to `file_path` reuse hiding distinct types. 3) B3 backward bbox misaligned (full body vs head).

## Root Cause

- **Laravel `ProcessAnalysisJob.php:258`:** `glob()[0]` always first file `0184bc4d.jpg` → 6 dashboard files all `be1b3367` identical despite distinct `file_path`. No `$eventIndex`/`$frameNumber` mapping.
- **Source path:** `base_path('../ai-service/evidence')` without `realpath()` could miss shared mount.
- **Ordering:** `sort($files)` alphabetical `0184,086c,...` ≠ `filemtime` order `ebbb,9d01,...` (true frame order, since AI saves per `packet.frame_index` sequentially). Events sorted by `started_at_frame` 102/132/165/195/240/267 mismatched.
- **Cache:** `dashboard/storage/app/private/evidence/1/` retained old duplicates; `whereHas` check still saw stale basenames, so `unused` logic picked wrong.
- **DB type:** `DetectionEvent` creation used `$mapped` correctly but evidence duplication made UI appear same type (same image hides distinct `S3/B4` badges).
- **Python `annotator.py:99`:** Drew full person bbox for B3/B1/B2/B5; B3 should be head top 30% centered above head.
- **Python `manager.py:84`:** Filename `jobId_evidenceId.jpg` without `frame_number/track_id` → overwrite risk, no traceability.

## Evidence

- AI `ai-service/evidence/774baa62/`: 8 distinct files hashes `be1b33,a3f9e1,9fe8bc,8430bf,5ea5b2,bf24,fd33,26bd` (verified `check_ai_evidence.py`).
- Dashboard before fix: 6 files all `68741 be1b33675909` (`check_evidence_files.py`), DB `COUNT DISTINCT checksum=1` vs `COUNT(*)=6`.
- `annotator.py` test `test_single_student_highlight` failed when head logic applied to B1 (full body expected) → confirms head vs body distinction.
- `ProcessAnalysisJob` log before fix: no `frame_number`/`event_index` logged.

## Fix Applied

### 1. Python `annotator_bbox_fix.patch` (`ai-service/app/evidence/annotator.py:36,94`)
- Add `_head_bbox_from_person()` head 30% height, 80% width centered.
- `if event_code=="B3": bbox=_head_bbox_from_person(bbox,0.30)` → box directly above head.

### 2. Python `manager.py:84-90`
- Filename now `jobId_000102_t2_evidenceId.jpg` includes `frame_number` `track_id` → no overwrite.
- `print(f"[Evidence] frame={frame_number} track={track_id} bbox={bbox} hash={checksum[:12]} file={filename}")` per save for audit.

### 3. PHP `evidence_frame_mapping_fix.patch` (`ProcessAnalysisJob.php:271-315`)
- `realpath()` + `usort(filemtime)` (frame time order).
- `$frameNumber = evData['frame_number'] ?? event->started_at_frame`
- `$orderedIds = pluck(id) orderBy started_at_frame, id` → `$eventIndex = array_search(event->id)`
- `$copiedBasenames` via `whereHas` + basename strip; `$unused` = not in copied; `$src = unused[0]` or `files[eventIndex]` frame-aware; `Log::info('copyEvidence mapping', [event_id,frame_number,eventIndex,src,...])` for audit.
- Store `frame_number`/`captured_at_seconds` per event (`EventEvidence::create` with `evData` values ensures distinct) and optional `bbox_json` (via `EventEvidence` fillable `bbox_json`, `Schema::hasColumn` check).

### 4. Laravel Model `EventEvidence.php:14` add `bbox_json` fillable.

### 5. Cache Clear
- Before re-run: `rm -rf storage/app/private/evidence/{job_id}` + `DELETE FROM event_evidence WHERE analysis_job_id=?` (see `repair_evidence_frame_mapping.sql`).

## Validation Steps

- Clear cache as above, re-run `python repair_frame_mapping.py` (mtime-sorted copy: `102→ebbb,132→9d01,165→3cf2,195→a9de,240→995b,267→086c` distinct).
- SQL `validate_evidence_frame_mapping.sql`:
  - `COUNT DISTINCT checksum = COUNT(*)` → 6=6 PASS
  - `GROUP BY checksum HAVING c>1` → 0 rows
  - `de.started_at_frame = ee.frame_number` all OK
  - `GROUP_CONCAT DISTINCT event_type` → `B4,S3` distinct
- `python -m pytest ai-service/tests/test_evidence_annotation.py` 11/11 pass (B3 head, B1/B2 full body)
- `php artisan test` 171/171 pass
- Visual: `detection-events/show` for B3 now shows head box red above head at `108,100,172,124` for person `100,100,180,200`, distinct per event (S3 gray, B4 red, B3 orange).

## Audit Checklist

- [ ] `Log::info copyEvidence mapping` shows distinct `src` per `event_id:frame_number`
- [ ] `EvidenceManager` print shows `frame track bbox hash file` distinct
- [ ] `event_evidence.frame_number` matches `detection_events.started_at_frame`
- [ ] `checksum_sha256` distinct per `analysis_job_id`
- [ ] B3 bbox top 30% verified above head, not full body
- [ ] No `files[0]` hardcoded, no alphabetical sort alone, `usort(filemtime)` used
- [ ] Cache cleared before re-run
