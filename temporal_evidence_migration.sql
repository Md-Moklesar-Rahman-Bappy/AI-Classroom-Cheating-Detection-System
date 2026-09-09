-- Temporal Evidence Migration for S3/B4 two-frame evidence
-- This migration adds new columns to support storing last-detection and trigger frame references separately.
-- Do NOT run migrate:fresh. This is a non-destructive additive migration.

-- Add new columns to detection_events table
ALTER TABLE detection_events ADD COLUMN trigger_frame_number INT DEFAULT NULL AFTER end_frame;
ALTER TABLE detection_events ADD COLUMN trigger_timestamp_seconds DOUBLE DEFAULT NULL AFTER trigger_frame_number;
ALTER TABLE detection_events ADD COLUMN last_detection_frame_number INT DEFAULT NULL AFTER trigger_timestamp_seconds;
ALTER TABLE detection_events ADD COLUMN last_detection_timestamp_seconds DOUBLE DEFAULT NULL AFTER last_detection_frame_number;
ALTER TABLE detection_events ADD COLUMN last_detection_bbox_json JSON NULL AFTER last_detection_timestamp_seconds;
ALTER TABLE detection_events ADD COLUMN absence_processed_frames INT DEFAULT NULL AFTER last_detection_bbox_json;
ALTER TABLE detection_events ADD COLUMN absence_source_frames_json JSON NULL AFTER absence_processed_frames;
ALTER TABLE detection_events ADD COLUMN bbox_format VARCHAR(10) DEFAULT 'xyxy' AFTER absence_source_frames_json;
ALTER TABLE detection_events ADD COLUMN processed_frame_width INT DEFAULT 640 AFTER bbox_format;
ALTER TABLE detection_events ADD COLUMN processed_frame_height INT DEFAULT 360 AFTER processed_frame_width;
ALTER TABLE detection_events ADD COLUMN source_frame_width INT DEFAULT 64 AFTER processed_frame_height;
ALTER TABLE detection_events ADD COLUMN source_frame_height INT DEFAULT 48 AFTER source_frame_width;

-- Add new columns to event_evidence table
ALTER TABLE event_evidence ADD COLUMN render_mode VARCHAR(20) DEFAULT 'trigger' AFTER file_type;
ALTER TABLE event_evidence ADD COLUMN trigger_frame_number INT DEFAULT NULL AFTER render_mode;
ALTER TABLE event_evidence ADD COLUMN last_detection_frame_number INT DEFAULT NULL AFTER trigger_frame_number;
ALTER TABLE event_evidence ADD COLUMN last_detection_bbox_json JSON NULL AFTER last_detection_frame_number;
ALTER TABLE event_evidence ADD COLUMN two_frame_evidence_json JSON NULL AFTER last_detection_bbox_json;
ALTER TABLE event_evidence ADD COLUMN presence_valid_detection_frame INT DEFAULT NULL AFTER two_frame_evidence_json;
ALTER TABLE event_evidence ADD COLUMN absence_source_frames_json JSON NULL AFTER presence_valid_detection_frame;

-- Create index for faster queries on temporal evidence
CREATE INDEX idx_detection_events_trigger_frame ON detection_events(trigger_frame_number);
CREATE INDEX idx_detection_events_last_detection_frame ON detection_events(last_detection_frame_number);
CREATE INDEX idx_event_evidence_render_mode ON event_evidence(render_mode);
CREATE INDEX idx_event_evidence_trigger_frame ON event_evidence(trigger_frame_number);
CREATE INDEX idx_event_evidence_last_detection_frame ON event_evidence(last_detection_frame_number);

-- Backfill existing S3/B4 events with trigger_frame_number = started_at_frame
-- and last_detection_frame_number = started_at_frame (for backward compatibility)
UPDATE detection_events
SET trigger_frame_number = started_at_frame,
    last_detection_frame_number = started_at_frame,
    absence_processed_frames = ended_at_frame - started_at_frame,
    bbox_format = 'xyxy',
    processed_frame_width = 640,
    processed_frame_height = 360
WHERE event_code IN ('S3', 'B4')
  AND trigger_frame_number IS NULL;

-- Backfill event_evidence records
UPDATE event_evidence ee
JOIN detection_events de ON de.id = ee.detection_event_id
SET ee.trigger_frame_number = de.started_at_frame,
    ee.last_detection_frame_number = de.started_at_frame,
    ee.render_mode = 'trigger'
WHERE de.event_code IN ('S3', 'B4')
  AND ee.render_mode = 'trigger';

-- Mark existing S3/B4 records as legacy temporal-mismatch evidence
UPDATE event_evidence
SET render_mode = 'legacy_temporal_mismatch'
WHERE render_mode = 'trigger'
  AND detection_event_id IN (
    SELECT id FROM detection_events WHERE event_code IN ('S3', 'B4')
  );

-- Verify migration
SELECT 
    event_code,
    COUNT(*) as total,
    SUM(CASE WHEN trigger_frame_number IS NOT NULL THEN 1 ELSE 0 END) as has_trigger,
    SUM(CASE WHEN last_detection_frame_number IS NOT NULL THEN 1 ELSE 0 END) as has_last_detection,
    SUM(CASE WHEN render_mode = 'legacy_temporal_mismatch' THEN 1 ELSE 0 END) as legacy_count
FROM detection_events
WHERE event_code IN ('S3', 'B4')
GROUP BY event_code;

-- Verify no data loss
SELECT COUNT(*) as remaining_legacy
FROM event_evidence
WHERE render_mode = 'legacy_temporal_mismatch';
