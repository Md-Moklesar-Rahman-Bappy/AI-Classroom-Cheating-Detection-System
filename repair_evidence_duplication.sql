-- ============================================================================
-- Repair Script: Evidence Duplication Bug (ProcessAnalysisJob::copyEvidence)
-- Job correlation_id = d8deff47-1843-4416-a24c-e3fbd2111484
-- DB id = 1, remote_job_id = 774baa62-6b71-4465-8fd5-9661588cd08a
-- Root cause: copyEvidence() always used glob()[0] → all events shared 0184bc4d.jpg (be1b3367)
-- Fix applied: distinct per-event selection via unused filter + round-robin
-- ============================================================================
-- PREREQUISITES (run outside SQL):
-- 1. Ensure AI evidence exists: ai-service/evidence/774baa62-6b71-4465-8fd5-9661588cd08a/*.jpg (8 files)
-- 2. For each event, copy distinct AI file to dashboard/storage/app/private/evidence/1/
--    cp ai-service/evidence/774baa62/..._0184bc4d.jpg → dashboard/storage/app/private/evidence/1/1_..._0184bc4d.jpg
--    cp ..._086c3bee.jpg → 2_..._086c3bee.jpg  etc. in frame order (102,132,165,195,240,267)
-- 3. Then run this SQL in ai_classroom DB.
-- ============================================================================

START TRANSACTION;

-- 0. Verify pre-state (audit)
SELECT de.id, de.event_type, de.temporary_track_id, de.started_at_frame,
       ee.id AS evidence_id, ee.file_path, LEFT(ee.checksum_sha256,12) AS hash12
FROM detection_events de
LEFT JOIN event_evidence ee ON ee.detection_event_id = de.id
WHERE de.analysis_job_id = 1
ORDER BY de.started_at_frame;

-- Expected pre-fix: all 6 rows share hash be1b33675909 (0184bc4d.jpg)

-- 1. Delete corrupted evidence rows for this job
DELETE ee FROM event_evidence ee
INNER JOIN detection_events de ON de.id = ee.detection_event_id
WHERE de.analysis_job_id = 1;

-- 2. Delete orphaned files on disk manually (see prerequisites) - SQL cannot delete files
--    PowerShell: Remove-Item dashboard/storage/app/private/evidence/1/*_774baa62*0184bc4d*.jpg

-- 3. Re-insert correct evidence rows in frame order (distinct per event)
--    Order: frame 102→112→165→195→240→267 maps to AI files sorted (see repair_evidence.py)

INSERT INTO event_evidence
  (detection_event_id, file_path, file_type, frame_number, captured_at_seconds, checksum_sha256, created_at, updated_at)
VALUES
  (1, 'evidence/1/1_774baa62-6b71-4465-8fd5-9661588cd08a_0184bc4d-0380-44dd-b104-b1c07dae0cfd.jpg', 'snapshot', 102, 3.4, 'be1b336759095ee7d4e59413cb8a3379110799bc3fb5041d4aa25a74c527dbd4', NOW(), NOW()),
  (2, 'evidence/1/2_774baa62-6b71-4465-8fd5-9661588cd08a_086c3bee-4182-4f98-a5b8-38c4fe1ef3b6.jpg', 'snapshot', 132, 4.4, 'a3f9e1de22bf58f95fd4d1d5d806f3d5139f88298b6a9bae1400d74e11ee0266', NOW(), NOW()),
  (3, 'evidence/1/3_774baa62-6b71-4465-8fd5-9661588cd08a_3cf29eb5-f621-4f28-9582-4b7cfea4a3d9.jpg', 'snapshot', 165, 5.5, '9fe8bc49ceb5375e706846938e930c3ff245f6893311fa7d68d67162a0cba7d8', NOW(), NOW()),
  (4, 'evidence/1/4_774baa62-6b71-4465-8fd5-9661588cd08a_724cf1a4-0286-4d02-8e86-75b8c89669b6.jpg', 'snapshot', 195, 6.5, '8430bf1cbe9997bd7d5704c54b7f1b7860034265e2de34305a9e12ab993ec15b', NOW(), NOW()),
  (5, 'evidence/1/5_774baa62-6b71-4465-8fd5-9661588cd08a_995b8c96-5fa0-4af8-a91c-782146033af1.jpg', 'snapshot', 240, 8.0, '5ea5b2d193379c84ec62208a9cf68572b6aad68b992dc3c6903251e78899bd4f', NOW(), NOW()),
  (6, 'evidence/1/6_774baa62-6b71-4465-8fd5-9661588cd08a_9d01d4ef-69e3-4f04-a549-9bf47209aa9c.jpg', 'snapshot', 267, 8.9, 'bf241b8da3ed82459977b8a51c5103b02309a6d518d9608e42f0b1f4e4b2bdfb', NOW(), NOW());

-- 4. Mark evidence available
UPDATE detection_events SET evidence_available = 1 WHERE analysis_job_id = 1;

-- 5. Verify post-state: 6 distinct hashes, 6 distinct file_path
SELECT de.id, de.event_type, de.started_at_frame,
       ee.file_path, LEFT(ee.checksum_sha256,12) AS hash12,
       ee.frame_number, ee.captured_at_seconds
FROM detection_events de
JOIN event_evidence ee ON ee.detection_event_id = de.id
WHERE de.analysis_job_id = 1
ORDER BY de.started_at_frame;

-- Expected post-fix: hashes be1b33, a3f9e1, 9fe8bc, 8430bf, 5ea5b2, bf24 distinct
-- Also verify: SELECT COUNT(DISTINCT checksum_sha256) = 6

COMMIT;

-- ============================================================================
-- GENERIC TEMPLATE for any future corrupted job (replace IDs):
-- ============================================================================
-- -- For job with analysis_job_id = :jobId and remote_job_id = :remoteId
-- -- 1. List AI files sorted: SELECT sorted glob(:aiBase/:remoteId/*.jpg)
-- -- 2. DELETE ee FROM event_evidence ee JOIN detection_events de ON de.id=ee.detection_event_id WHERE de.analysis_job_id=:jobId;
-- -- 3. For each event in ORDER BY started_at_frame, insert next unused AI file (round-robin)
-- --    file_path = CONCAT('evidence/', :jobId, '/', event_id, '_', basename(ai_file))
-- --    checksum = SHA256(file)
-- -- 4. UPDATE detection_events SET evidence_available=1 WHERE analysis_job_id=:jobId;
-- ============================================================================

-- ============================================================================
-- PYTHON REPAIR ALTERNATIVE (reproducible, handles file copy + checksum):
-- See repair_evidence.py in repo root — does glob, sort, delete, copy, insert
-- Run: python repair_evidence.py  (requires pymysql, handles file I/O + DB)
-- ============================================================================
