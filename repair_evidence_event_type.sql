-- Repair Evidence Event Type & Frame Number (duplication persists fix)
-- Root: ProcessAnalysisJob used files[0] + alphabetical + stale cache; event_evidence missing event_type/bbox_json

-- 0. Add columns if missing (for overlay & type audit)
ALTER TABLE event_evidence ADD COLUMN IF NOT EXISTS event_type VARCHAR(10) NULL AFTER detection_event_id;
ALTER TABLE event_evidence ADD COLUMN IF NOT EXISTS bbox_json JSON NULL AFTER checksum_sha256;

-- 1. Cache clear must be done first in shell:
-- rm -rf storage/app/private/evidence/{job_id}  (e.g., 1)
-- Replace {job_id} with analysis_job_id (e.g., 1 for d8deff47)

-- 2. Delete duplicated evidence rows for affected job(s)
DELETE ee FROM event_evidence ee
JOIN detection_events de ON de.id = ee.detection_event_id
WHERE de.analysis_job_id IN (
  SELECT id FROM analysis_jobs WHERE correlation_id='d8deff47-1843-4416-a24c-e3fbd2111484'
  -- Add more ids: , (SELECT id FROM analysis_jobs WHERE remote_job_id='774baa62-...')
);

-- 3. Re-insert correct rows joining detection_events to copy true event_type & frame_number
--    This uses detection_events as source of truth (event_type = S3/B4/B3 etc., frame = started_at_frame)
--    File must have been correctly copied by fixed copyEvidence (distinct per frame_number via filemtime)
--    If re-running job, copyEvidence will re-create rows automatically; this SQL is for manual repair if job not re-run.

INSERT INTO event_evidence (detection_event_id, event_type, frame_number, file_path, file_type, captured_at_seconds, checksum_sha256, bbox_json, created_at, updated_at)
SELECT 
  de.id,
  de.event_type,
  de.started_at_frame,
  CONCAT('evidence/', de.analysis_job_id, '/', de.id, '_', LPAD(de.started_at_frame,6,'0'), '.jpg'),
  'snapshot',
  de.started_at_seconds,
  SHA2(CONCAT(de.id, de.started_at_frame, de.event_type), 256),
  NULL,
  NOW(), NOW()
FROM detection_events de
WHERE de.analysis_job_id = (SELECT id FROM analysis_jobs WHERE correlation_id='d8deff47-1843-4416-a24c-e3fbd2111484' LIMIT 1)
AND NOT EXISTS (SELECT 1 FROM event_evidence ee WHERE ee.detection_event_id = de.id);

-- Note: Above file_path/checksum are placeholders; real files are copied from ai-service/evidence/{remoteJobId}/
-- For production repair, run repair_frame_mapping.py which copies real files and sets real SHA256:
-- python repair_frame_mapping.py  (copies job_..._frame_..._track_..._eventcode.jpg and inserts true checksum)

-- 4. Update existing rows where event_type is NULL or mismatched (copy from detection_events)
UPDATE event_evidence ee
JOIN detection_events de ON de.id = ee.detection_event_id
SET ee.event_type = de.event_type,
    ee.frame_number = de.started_at_frame
WHERE ee.event_type IS NULL OR ee.event_type != de.event_type OR ee.frame_number != de.started_at_frame;

-- 5. Validation (must pass)
-- Distinct checksums per job
SELECT de.analysis_job_id, COUNT(*) AS total, COUNT(DISTINCT ee.checksum_sha256) AS distinct_hashes,
       CASE WHEN COUNT(*)=COUNT(DISTINCT ee.checksum_sha256) THEN 'PASS' ELSE 'FAIL' END AS result
FROM detection_events de JOIN event_evidence ee ON ee.detection_event_id=de.id
GROUP BY de.analysis_job_id;

-- No duplicate checksum per job
SELECT de.analysis_job_id, ee.checksum_sha256, COUNT(*) c
FROM event_evidence ee JOIN detection_events de ON de.id=ee.detection_event_id
GROUP BY de.analysis_job_id, ee.checksum_sha256 HAVING c>1;
-- Expected 0 rows

-- Distinct event types per job
SELECT de.analysis_job_id, GROUP_CONCAT(DISTINCT de.event_type ORDER BY de.event_type) AS types
FROM detection_events de GROUP BY de.analysis_job_id;
-- For d8deff47: S3,B4 (and after B3 fix: B3)

-- Frame mapping correctness
SELECT de.id, de.event_type AS de_type, ee.event_type AS ee_type, de.started_at_frame, ee.frame_number,
       CASE WHEN de.event_type=ee.event_type AND de.started_at_frame=ee.frame_number THEN 'OK' ELSE 'MISMATCH' END
FROM detection_events de JOIN event_evidence ee ON ee.detection_event_id=de.id
WHERE de.analysis_job_id=(SELECT id FROM analysis_jobs WHERE correlation_id='d8deff47-1843-4416-a24c-e3fbd2111484' LIMIT 1);

-- For overlay verification if bbox_json stored:
-- SELECT de.id, de.event_type, ee.bbox_json FROM detection_events de JOIN event_evidence ee ON ee.detection_event_id=de.id WHERE de.event_type='B3';
-- B3 bbox height should be ~30% of person (e.g., y_max - y_min ≈ 0.3*h)
