-- Repair/verify bounding box alignment for B3 (Looking Backward)
-- Context: EventEvidence currently has no bbox column (bbox baked into jpg via annotator)
-- This SQL verifies correct evidence distinctness and provides migration if bbox column added in future

-- 1. Verify B3 events have distinct evidence (after duplication fix)
SELECT de.id, de.event_type, de.temporary_track_id, de.started_at_frame,
       ee.file_path, LEFT(ee.checksum_sha256,12) AS hash12
FROM detection_events de
JOIN event_evidence ee ON ee.detection_event_id = de.id
WHERE de.event_type = 'B3'
ORDER BY de.started_at_frame;
-- Expected: distinct hashes per frame (head bbox distinct above head)

-- 2. If bbox columns are added in future (recommended for overlay), run:
-- ALTER TABLE event_evidence ADD COLUMN bbox_json JSON NULL AFTER file_type;
-- Then update B3 head region as top 30% of person bbox (if person bbox stored in detection_events.config or similar)
-- Example (if detection_events had person_bbox_json):
-- UPDATE event_evidence ee
-- JOIN detection_events de ON de.id = ee.detection_event_id
-- SET ee.bbox_json = JSON_OBJECT(
--   'x_min', JSON_EXTRACT(de.person_bbox, '$.x_min') + (JSON_EXTRACT(de.person_bbox, '$.x_max')-JSON_EXTRACT(de.person_bbox, '$.x_min'))*0.1,
--   'y_min', JSON_EXTRACT(de.person_bbox, '$.y_min'),
--   'x_max', JSON_EXTRACT(de.person_bbox, '$.x_max') - (JSON_EXTRACT(de.person_bbox, '$.x_max')-JSON_EXTRACT(de.person_bbox, '$.x_min'))*0.1,
--   'y_max', JSON_EXTRACT(de.person_bbox, '$.y_min') + (JSON_EXTRACT(de.person_bbox, '$.y_max')-JSON_EXTRACT(de.person_bbox, '$.y_min'))*0.30
-- )
-- WHERE de.event_type='B3';

-- 3. Verify no duplicated evidence files for same job (should be 0)
SELECT analysis_job_id, checksum_sha256, COUNT(*) c
FROM event_evidence ee
JOIN detection_events de ON de.id=ee.detection_event_id
GROUP BY analysis_job_id, checksum_sha256 HAVING c>1;
-- Expected: 0 rows after duplication fix

-- 4. For existing corrupted B3 images (if baked with full-body), re-process job:
-- php artisan queue:work --once  (or re-run ProcessAnalysisJob for job id)
-- New annotator will bake head bbox (108,100,172,124 for 100,100,180,200 example)
