-- Repair Evidence Frame Mapping (duplicate screenshots, same type)
-- Root: ProcessAnalysisJob::copyEvidence used files[0] + alphabetical sort, not frame order; annotator used full body for B3
-- Use after cache clear: rm -rf dashboard/storage/app/private/evidence/{job_id}

-- 0. Add bbox_json column if needed for overlay (optional, for future frontend bbox overlay)
ALTER TABLE event_evidence ADD COLUMN IF NOT EXISTS bbox_json JSON NULL AFTER checksum_sha256;

-- 1. Clear corrupted evidence for affected job (example d8deff47 → analysis_job_id lookup)
--    Run file deletion first: rm -rf storage/app/private/evidence/1  (job_id=1)
DELETE ee FROM event_evidence ee
JOIN detection_events de ON de.id = ee.detection_event_id
WHERE de.analysis_job_id = (SELECT id FROM analysis_jobs WHERE correlation_id='d8deff47-1843-4416-a24c-e3fbd2111484' LIMIT 1);

-- 2. Generic repair for any job: re-insert will be handled by next Laravel job run via copyEvidence
--    If manually repairing, copy distinct AI files in mtime order per frame_number:
--    AI files sorted by filemtime = frame order: ebbb,9d01,3cf2,a9de,995b,086c,724c,0184 for remote 774baa62
--    Event frames 102,132,165,195,240,267 map to first 6 mtime files respectively.

-- Example re-insert for job 1 (frame order mapping, distinct checksums):
INSERT INTO event_evidence (detection_event_id, file_path, file_type, frame_number, captured_at_seconds, checksum_sha256, bbox_json, created_at, updated_at)
SELECT de.id, CONCAT('evidence/', de.analysis_job_id, '/', de.id, '_', SUBSTRING_INDEX(ai_file, '/', -1)), 'snapshot', de.started_at_frame, de.started_at_seconds, ai_checksum, 
       CASE WHEN de.event_type='B3' THEN JSON_OBJECT('x_min', 108, 'y_min', 100, 'x_max', 172, 'y_max', 124) ELSE NULL END,
       NOW(), NOW()
FROM detection_events de
-- Cross join with ordered AI files (pseudo: replace with actual file list via repair_frame_mapping.py)
-- For production, run repair_frame_mapping.py which does file copy + checksum + bbox_json
LIMIT 0;

-- 3. Validation (must pass after repair)
-- Distinct checksums per job
SELECT de.analysis_job_id, COUNT(*) AS events, COUNT(DISTINCT ee.checksum_sha256) AS distinct_hashes
FROM detection_events de JOIN event_evidence ee ON ee.detection_event_id=de.id
GROUP BY de.analysis_job_id;

-- Should return distinct_hashes = events (e.g., 6=6)

-- Duplicate checksum per job must be 0
SELECT de.analysis_job_id, ee.checksum_sha256, COUNT(*) c
FROM event_evidence ee JOIN detection_events de ON de.id=ee.detection_event_id
GROUP BY de.analysis_job_id, ee.checksum_sha256 HAVING c>1;

-- Frame mapping correctness
SELECT de.id, de.event_type, de.started_at_frame AS event_frame, ee.frame_number AS evidence_frame,
       CASE WHEN de.started_at_frame=ee.frame_number THEN 'OK' ELSE 'MISMATCH' END
FROM detection_events de JOIN event_evidence ee ON ee.detection_event_id=de.id
WHERE de.analysis_job_id=(SELECT id FROM analysis_jobs WHERE correlation_id='d8deff47-1843-4416-a24c-e3fbd2111484' LIMIT 1);

-- Event type distinctness
SELECT de.analysis_job_id, GROUP_CONCAT(DISTINCT de.event_type ORDER BY de.event_type) AS types, COUNT(DISTINCT de.event_type) AS distinct_types
FROM detection_events de GROUP BY de.analysis_job_id;
-- For d8deff47: S3,B4 distinct

-- Cache clear verification
-- ls dashboard/storage/app/private/evidence/1/ | wc -l  should equal events count
-- hashes distinct: sha256sum evidence/1/* | cut -d' ' -f1 | sort -u | wc -l  equals events
