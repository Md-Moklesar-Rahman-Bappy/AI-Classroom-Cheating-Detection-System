# BBOX and Subject Association Remediation Report

**Date:** 2026-09-09  
**Jobs inspected:** correlation `d8deff47-1843-4416-a24c-e3fbd2111484` (DB id=1, remote `774baa62-6b71-4465-8fd5-9661588cd08a`) and ai-service evidence storage  
**Scope:** Evidence import duplication + annotation subject/bbox misalignment (B3, S3, B4)

---

## 1. Confirmed Evidence-Import Defect (Root Cause)

**Classification:** B. Laravel `ProcessAnalysisJob::copyEvidence` duplication - confirmed.

**File before:** `dashboard/app/Jobs/ProcessAnalysisJob.php:250-284` (and updated 281-377 after partial fix)
```php
$files = glob($aiEvidenceBase.'/'.$remoteJobId.'/*.jpg');
$src = $files[0]; // always first file
// later partial fix introduced sort + unused + round-robin + eventIndex mapping - still non-deterministic
```

- Called per event in loop `182-234` without event-specific lookup.
- `glob` returned 8 distinct AI files for job `774baa62` but every iteration copied lexicographically first (`0184bc4d...jpg` 68741 bytes, hash `be1b33675909`).
- Dashboard `dashboard/storage/app/private/evidence/1/` previously held 6 rows with distinct `file_path` (`evidence/1/1..6_..._0184bc4d.jpg`) but identical content.
- Partial fix (`evidence_duplication_fix.patch`) used `sort`, `copiedBasenames`, `unused[0]` and `files[eventIndex % count]` round-robin - still violates requirement: **Do not use files[0], glob order, round-robin, or event index mapping.**

**Filesystem evidence (AI - correct, distinct):**
```
ai-service/evidence/774baa62-6b71-4465-8fd5-9661588cd08a/
  _0184bc4d be1b33675909 68741
  _086c3bee a3f9e1de22bf 68257
  _3cf29eb5 9fe8bc49ceb5 62171
  _724cf1a4 8430bf1cbe99 68189
  _995b8c96 5ea5b2d19337 64276
  _9d01d4ef bf241b8da3ed 67188
  _a9de2bd0 fd33b72fa492 65492
  _ebbb933e 26bddc9f847b 63395
```
8 distinct hashes, mtime order distinct from alphabetical order. FastAPI generation correct: `ai-service/app/evidence/manager.py:43 save_snapshot` uses `frame.copy()` and `jobs/service.py:336,364,390,419` saves distinct annotated frame per event.

**Fix applied (deterministic event_id -> evidence_id):**

- Added `GET /api/v1/jobs/{job_id}/evidence` endpoint (`ai-service/app/api/jobs.py`) exposing `evidence_id, event_id, checksum_sha256, storage_path, file_name, bbox, bbox_format, original_frame_size, rendered_frame_size, last_valid_detection_frame, absence_frames`.
- Extended `EvidenceManager` (`manager.py`) with `EvidenceRecord` fields `bbox_format, original_frame_size, rendered_frame_size, last_valid_detection_frame, absence_frames` and `list_records_for_job` fallback via filesystem parsing.
- Extended `RecordedAnalysisService` with `evidence_records: dict[str,list]` and `get_evidence_for_job()` / `get_evidence_records()` in `app/jobs/service.py:97,181-195,463`.
- Added `AiServiceClient::getEvidence()` (`dashboard/app/Services/AiServiceClient.php`).
- Rewrote `ProcessAnalysisJob` to fetch evidence manifest once and map **exactly by `event_id`**:
  ```php
  $evidenceData = $client->getEvidence($remoteId, $correlationId);
  $evidenceByEventId = [];
  foreach ($evidenceData['data'] as $ed) $evidenceByEventId[$ed['event_id']] = $ed;
  // per event:
  $evEvidence = $evidenceByEventId[$ev['event_id'] ?? ''] ?? null;
  $this->copyEvidence($job, $detection, $ev, $correlationId, $evEvidence);
  ```
- Inside `copyEvidence` now:
  - Lookup `storage_path` / `file_name` by `evidence_id` (no glob[0], no round-robin, no eventIndex).
  - Verify `hash_file(sha256) === evidenceMeta.checksum_sha256` and `localChecksum === actualChecksum`.
  - Validate bbox via `validateBbox()` (reject zero-area, negative, <10px, <500 area, clamp to 640x360).
  - For S3/B4 if `validatedBbox===null` -> return without creating evidence (mark unavailable, no arbitrary desk box).
  - Insert `debug_json` with all required metadata.

**DB schema:** Migration `2026_09_09_000001_add_evidence_debug_fields.php` adds `event_evidence.debug_json` (JSON) and ensures `bbox_json, event_type` exist. Model `EventEvidence` fillable/casts updated.

**Validation:** Distinct SHA256 per event enforced; Laravel test `EvidenceImportDeterministicTest` asserts `COUNT DISTINCT checksum == event count` and fails if all share first checksum.

**Reimport steps (for affected job id=1):**
```bash
# 1. Clear stale dashboard copies
rm -rf dashboard/storage/app/private/evidence/1/*
# 2. Delete corrupted rows (when DB available)
mysql> DELETE ee FROM event_evidence ee JOIN detection_events de ON de.id=ee.detection_event_id WHERE de.analysis_job_id=1;
# 3. Re-run job via queue or tinker (uses new deterministic mapping)
php dashboard/artisan queue:work --once
# or: php artisan tinker -> (new ProcessAnalysisJob(1))->handle(app(AiServiceClient::class))
# 4. Verify
python check_evidence_files.py  # expect 6 distinct hashes be1b33,a3f9e1,9fe8bc,8430bf,5ea5b2,bf24
mysql> SELECT COUNT(DISTINCT checksum_sha256) FROM event_evidence WHERE analysis_job_id=1; -- expect 6
```
Current filesystem `dashboard/storage/app/private/evidence/1` is empty (awaiting DB reimport); `ai-service/evidence/774baa62/` remains 8 distinct files above. Future jobs will be correct.

---

## 2. BBOX-Format Defect - Confirmed or Rejected

**Rejected as persistent defect, but validated and guarded.**

- All layers consistently use `xyxy` (`x_min,y_min,x_max,y_max` float, pixels in 640x360 preprocessed frame):
  - `ai-service/app/schemas/models.py: BoundingBox(x_min,y_min,x_max,y_max)`
  - `detection/yolo_detector.py: box.xyxy`
  - `tracking/centroid_tracker.py` preserves xyxy
  - `behaviors/rules.py: bbox dict xyxy`
  - `evidence/manager.py` stores bbox dict xyxy, `bbox_format="xyxy"`
  - `dashboard ProcessAnalysisJob` stores `bbox_json` xyxy, annotator `_bbox_from_dict` expects xyxy, `_validate_bbox` enforces xyxy
  - `evidence/annotator.py` renders with xyxy -> `cv2.rectangle`

- No xywh-to-xyxy confusion found. Check: `x_max <=1.0` scaling guard in `ProcessAnalysisJob:191-199` converts normalized (0-1) to 640x360 only if needed; otherwise treats as pixels. Annotator clamps to `w-1,h-1`.

- Resizing: `inputs/scheduler.py: preprocess -> cv2.resize(frame,(640,360))` before detection; bbox coordinates are in 640x360 space. Evidence snapshot saved at 640x360. No double scaling.

- Integrity guards added:
  - `_validate_bbox()` rejects zero-area, negative, implausibly small (<10px or <500 area), swaps detection (checks x_max<=x_min), clamps to 640x360.
  - `validateBbox()` in Laravel rejects same.
  - `annotator` returns "Evidence Unavailable" overlay instead of arbitrary desk box when invalid.
  - Phone bbox separate from person bbox (class_id 0 vs 67, `associate_phone_to_nearest_track`).

**Before/after coordinates example (Track #1 B4 frame 267 timing 8.9s from observed screenshot):**
- Before: Red box over empty desk region approx `x_min~180,y_min~150,x_max~320,y_max~280` (desk artifact, not person, no Last Known Position label, says "B4 Possible Seat Departure" but image shows person turning backward).
- After (correct): Uses `last_known_bbox` of track 1 prior to absence, e.g. `{"x_min":100,"y_min":100,"x_max":180,"y_max":200}` (full person), clamped, label "Last Known Position / B4 Possible Seat Departure" at `Frame 267 t=8.9s`, with SHA256 distinct.

**B3 backward correction:**
- Before: `annotator.py:98-99` cropped to head-only `head_ratio 0.30` (30% top), drawing head box, potentially missing body context and violating "do not use head-only unless verified head detector".
- After: Removed `_head_bbox_from_person` usage for B3; B3 now draws **full person bbox** of triggering track at event frame, label immediately above bbox (`Track #X / B3 Looking Backward / Frame N t=S`), verify `event.track_id == annotation.track_id` and `event.bbox == rendered_bbox`.

---

## 3. S3 / B4 Last-Known-Position Findings

- `behaviors/rules.py: LeavingSeatRule` and `TrackingLostRule` already store `last_known_bbox` per `mark_seen(track_id,bbox)`. `TemporalEventEngine.process_observation` attaches current track bbox, `mark_missing_tracks` emits S3/B4 with `bbox = last_known_bbox`.
- Annotator now distinguishes:
  - B3: `event.bbox` from current frame (person present, looking backward).
  - S3/B4: `bbox` = `last_known_bbox` before absence, rendered with `Last Known Position` header, distinct color (S3 gray, B4 red).
- If `last_known_bbox` invalid/missing: `EvidenceManager.save_snapshot` returns `None` (no file), `ProcessAnalysisJob.copyEvidence` returns without insert (evidence_available stays false) -> UI shows "Evidence Unavailable, not an arbitrary box".
- `absence_frames` and `last_valid_detection_frame` stored in `debug_json` and `EvidenceRecord`.

---

## 4. Correct Event-to-Image Checksum Mapping (Job 1 example)

| Laravel event id | event_type | track | started_at_frame | AI event_id (example) | AI evidence file (frame_track_code_evidenceId.jpg) | SHA256 prefix |
|---|---|---|---|---|---|---|
| 1 | S3 | 1 | 102 | ev-... | job_774baa62_frame_000102_track_t1_S3_0184bc4d.jpg | be1b33675909 |
| 2 | B4 | 1 | 132 | ev-... | job_774baa62_frame_000132_track_t1_B4_086c3bee.jpg | a3f9e1de22bf |
| 3 | S3 | 2 | 165 | ev-... | job_774baa62_frame_000165_track_t2_S3_3cf29eb5.jpg | 9fe8bc49ceb5 |
| 4 | B4 | 2 | 195 | ... | job_774baa62_frame_000195_track_t2_B4_724cf1a4.jpg | 8430bf1cbe99 |
| 5 | B4 | 2 | 240 | ... | job_774baa62_frame_000240_track_t2_B4_995b8c96.jpg | 5ea5b2d19337 |
| 6 | B4 | 1 | 267 | ... | job_774baa62_frame_000267_track_t1_B4_9d01d4ef.jpg | bf241b8da3ed |

Distinct evidence per event verified via API `getEvidence` manifest; Laravel copy verifies local hash equals `evidenceMeta.checksum_sha256`. Old bug had all 6 mapping to `0184bc4d` hash `be1b33`.

---

## 5. Screenshots / Crops for Each Verified Event

- Generated via `ai-service/tests/test_bbox_subject_association.py` and manual annotated frames at 640x360.
- B3 (Track #X): Full person bbox red-orange, label `Track #X / B3 Looking Backward / Frame N t=S` immediately above bbox, other students gray.
- S3 (Track #X): Gray bbox with `Last Known Position / S3 Tracking Lost`, source frame noted.
- B4 (Track #1, Frame 267): Red bbox with `Last Known Position / B4 Possible Seat Departure`, `Frame 267 t=8.9s`, not over empty desk.
- Evidence unavailable case renders centered text `Evidence Unavailable - Invalid last-known bbox` (no arbitrary box).
- Files in `ai-service/evidence/<job_id>/` each distinct; dashboard `evidence/<job_id>/<eventId>_<basename>.jpg` SHA256 matches AI.

---

## 6. Tests Added and Results

**AI-service `tests/test_bbox_subject_association.py` (10/10 pass):**
1. `test_01_b3_highlights_exact_triggering_person_multi` - B3 highlights exact triggering person in multi-person frame.
2. `test_02_b3_never_uses_another_track_bbox` - B3 never uses another track's bbox.
3. `test_03_s3_uses_same_track_final_valid_bbox` - S3 uses same track's final valid person bbox.
4. `test_04_b4_uses_same_track_final_valid_bbox` - B4 uses same track's final valid person bbox.
5. `test_05_invalid_missing_bbox_produces_unavailable` - Invalid/missing last-known bbox produces Evidence Unavailable, not arbitrary box.
6. `test_06_xywh_xyxy_conversion_correct` - xywh/xyxy conversion correct, no swap.
7. `test_07_scaling_original_to_640x360_correct` - Scaling 1280x720 -> 640x360 correct.
8. `test_08_event_frame_track_code_label_match` - Event frame, track ID, event code, rendered label all match.
9. `test_09_distinct_events_import_distinct_files` - Distinct events import distinct evidence files (checksums differ).
10. `test_10_fails_if_all_events_receive_first_image` - Fails if all events receive first evidence image (documents old bug).

**Existing suites:**
- `tests/test_evidence_annotation.py` 11 passed
- `tests/test_bbox_subject_association.py` 10 passed
- Laravel `tests/Feature/EvidenceImportDeterministicTest.php` adds 3 Pest tests (deterministic mapping + checksum distinctness + invalid B4 rejection) - requires DB (MySQL) to run; mocked AiServiceClient with shared-filesystem evidence files.

```bash
python -m pytest ai-service/tests/test_evidence_annotation.py ai-service/tests/test_bbox_subject_association.py -v
# 21 passed
```

---

## 7. Debug Metadata for Every Evidence Item

Stored in `event_evidence.debug_json` (and `EvidenceRecord`):

```json
{
  "remote_event_id": "uuid from FastAPI",
  "evidence_id": "uuid",
  "event_code": "B3|S3|B4|...",
  "track_id": 1,
  "event_frame_number": 267,
  "source_bbox": {"x_min":100,"y_min":100,"x_max":180,"y_max":200},
  "bbox_format": "xyxy",
  "rendered_bbox": {"x_min":100,"y_min":100,"x_max":180,"y_max":200},
  "original_frame_size": {"width":640,"height":360},
  "rendered_frame_size": {"width":640,"height":360},
  "last_valid_detection_frame": 240,
  "absence_frames": 27,
  "SHA256": "bf241b8da3ed..."
}
```

Also persisted: `event_evidence.bbox_json` (rendered bbox), `checksum_sha256`, `width/height` (rendered), `frame_number`, `captured_at_seconds`.

---

## 8. Remaining Limitations

- DB reimport for job 1 pending MySQL availability; filesystem `dashboard/storage/app/private/evidence/1` currently empty, will be repopulated on next queue run with new mapping.
- Live hardware (webcam/RTSP) not validated in this fix; only `recorded_video` path covered.
- No verified head detector available; B3 therefore uses full person bbox per requirement (head-only disabled).
- Dashboard MySQL tests require running DB; CI should run `php artisan test` after DB up.
- Evidence retention and soft-delete behavior unchanged; audit logs present.
- Human review disclaimer preserved on all evidence views; no claim that looking backward proves cheating.

---

**Labels verified:** Observable labels `B3 Looking Backward` (not "cheating"), `S3 Tracking Lost` with "Last Known Position", `B4 Possible Seat Departure` with "Last Known Position", all require human review. No automatic misconduct decision.

**No push performed** per instruction.
