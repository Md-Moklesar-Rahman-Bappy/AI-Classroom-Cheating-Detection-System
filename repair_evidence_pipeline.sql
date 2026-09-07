-- Repair Evidence Pipeline (duplication + bbox + type)
-- Root: files[0] + alphabetical sort + stale cache + full-body for B3
-- Affected: analysis_jobs with correlation d8deff47 (id=1, remote 774baa62) — template below generic

-- 0. Add bbox_json for overlay if missing
ALTER TABLE event_evidence ADD COLUMN IF NOT EXISTS bbox_json JSON NULL AFTER checksum_sha256;

-- 1. Cache clear (run in shell BEFORE SQL):
-- rm -rf storage/app/private/evidence/1
-- rm -rf ../ai-service/evidence cache is kept (AI distinct files remain)

-- 2. Delete corrupted evidence rows for job
DELETE ee FROM event_evidence ee
JOIN detection_events de ON de.id = ee.detection_event_id
WHERE de.analysis_job_id = (SELECT id FROM analysis_jobs WHERE correlation_id='d8deff47-1843-4416-a24c-e3fbd2111484' LIMIT 1);

-- 3. Verify deletion
SELECT COUNT(*) FROM event_evidence ee JOIN detection_events de ON de.id=ee.detection_event_id
WHERE de.analysis_job_id = (SELECT id FROM analysis_jobs WHERE correlation_id='d8deff47-1843-4416-a24c-e3fbd2111484' LIMIT 1);
-- Expected 0

-- 4. Re-insert will be done by Laravel job re-run (copyEvidence now frame-aware)
--    Alternatively, manual insert using AI files sorted by filemtime (frame order):
--    Use repair_frame_mapping.py which does: glob sorted by mtime, map event frame 102→ebbb,132→9d01,165→3cf2,195→a9de,240→995b,267→086c
--    Example manual (if re-run not possible):
-- INSERT INTO event_evidence (detection_event_id, file_path, file_type, frame_number, captured_at_seconds, checksum_sha256, bbox_json, created_at, updated_at)
-- VALUES (1, 'evidence/1/1_774baa62_..._ebbb...jpg','snapshot',102,3.4,'ebbbhash', JSON_OBJECT('x_min',108,'y_min',100,'x_max',172,'y_max',124), NOW(), NOW()), ...
-- For B3 events, bbox_json should be head region top 30%: {"x_min":hx1,"y_min":y_min,"x_max":hx2,"y_max":y_min+0.3*h}

-- 5. Validation (must pass)
-- Distinct checksums per job
SELECT de.analysis_job_id, COUNT(*) AS events, COUNT(DISTINCT ee.checksum_sha256) AS distinct_hashes,
       CASE WHEN COUNT(*)=COUNT(DISTINCT ee.checksum_sha256) THEN 'PASS' ELSE 'FAIL' END
FROM detection_events de JOIN event_evidence ee ON ee.detection_event_id=de.id
GROUP BY de.analysis_job_id;
-- Expected 6=6 for repaired job

-- Duplicate checksum per job must be 0
SELECT de.analysis_job_id, ee.checksum_sha256, COUNT(*) c
FROM event_evidence ee JOIN detection_events de ON de.id=ee.detection_event_id
GROUP BY de.analysis_job_id, ee.checksum_sha256 HAVING c>1;

-- Frame mapping
SELECT de.id, de.event_type, de.started_at_frame AS event_frame, ee.frame_number AS evidence_frame,
       CASE WHEN de.started_at_frame=ee.frame_number THEN 'OK' ELSE 'MISMATCH' END
FROM detection_events de JOIN event_evidence ee ON ee.detection_event_id=de.id
WHERE de.analysis_job_id=(SELECT id FROM analysis_jobs WHERE correlation_id='d8deff47-1843-4416-a24c-e3fbd2111484' LIMIT 1);

-- Distinct types per job
SELECT de.analysis_job_id, GROUP_CONCAT(DISTINCT de.event_type) AS types
FROM detection_events de GROUP BY de.analysis_job_id;
-- Expected S3,B4,B3 distinct

-- B3 head bbox verification (if bbox_json stored)
SELECT de.id, de.event_type, ee.bbox_json FROM detection_events de JOIN event_evidence ee ON ee.detection_event_id=de.id WHERE de.event_type='B3';
-- For B3, bbox height should be ~30% of person (e.g., y_max - y_min ≈ 0.3*h)

-- Generic template for any job: replace :jobId and :remoteId
-- DELETE ee FROM event_evidence ee JOIN detection_events de ON de.id=ee.detection_event_id WHERE de.analysis_job_id=:jobId;
-- Then re-run ProcessAnalysisJob for :jobId (php artisan queue:work --once) which will use fixed copyEvidence frame-aware mapping
