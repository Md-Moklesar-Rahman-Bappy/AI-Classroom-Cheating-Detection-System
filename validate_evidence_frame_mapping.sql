-- Validation: Evidence frame mapping distinctness
-- Run in ai_classroom DB after fix

-- 1. Each event should have one distinct evidence, distinct checksum per job
SELECT de.analysis_job_id, COUNT(*) AS events, COUNT(DISTINCT ee.checksum_sha256) AS distinct_hashes,
       CASE WHEN COUNT(*) = COUNT(DISTINCT ee.checksum_sha256) THEN 'PASS' ELSE 'FAIL - duplicate' END AS result
FROM detection_events de
JOIN event_evidence ee ON ee.detection_event_id = de.id
GROUP BY de.analysis_job_id;

-- Expected: distinct_hashes = events (e.g., 6=6 for job d8deff47) -> PASS

-- 2. Per-job detail (for job d8deff47 → analysis_job_id lookup via correlation)
SELECT de.id AS event_id, de.event_type, de.temporary_track_id, de.started_at_frame,
       ee.file_path, LEFT(ee.checksum_sha256,12) AS hash12, ee.frame_number AS ev_frame
FROM detection_events de
JOIN event_evidence ee ON ee.detection_event_id = de.id
WHERE de.analysis_job_id = (SELECT id FROM analysis_jobs WHERE correlation_id='d8deff47-1843-4416-a24c-e3fbd2111484' LIMIT 1)
ORDER BY de.started_at_frame;
-- Expected: 6 rows with distinct hash12: be1b33, a3f9e1, 9fe8bc, 8430bf, 5ea5b2, bf24

-- 3. Detect duplicated file_path reuse (should be 0)
SELECT file_path, COUNT(*) c FROM event_evidence GROUP BY file_path HAVING c>1;

-- 4. Detect duplicated checksum reuse per job (should be 0)
SELECT de.analysis_job_id, ee.checksum_sha256, COUNT(*) c
FROM event_evidence ee JOIN detection_events de ON de.id=ee.detection_event_id
GROUP BY de.analysis_job_id, ee.checksum_sha256 HAVING c>1;

-- 5. Frame mapping correctness: evidence frame_number should match event started_at_frame
SELECT de.id, de.started_at_frame AS event_frame, ee.frame_number AS evidence_frame,
       CASE WHEN de.started_at_frame = ee.frame_number THEN 'OK' ELSE 'MISMATCH' END
FROM detection_events de JOIN event_evidence ee ON ee.detection_event_id=de.id
WHERE de.analysis_job_id = (SELECT id FROM analysis_jobs WHERE correlation_id='d8deff47-1843-4416-a24c-e3fbd2111484' LIMIT 1);

-- 6. Full audit for all jobs (if any FAIL, needs repair)
SELECT 'Frame mapping audit' AS check_name,
       SUM(CASE WHEN de.started_at_frame = ee.frame_number THEN 0 ELSE 1 END) AS mismatches
FROM detection_events de JOIN event_evidence ee ON ee.detection_event_id=de.id;
