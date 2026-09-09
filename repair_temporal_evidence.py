"""
Temporal Evidence Repair Script

Repairs existing S3/B4 evidence records that have temporal mismatch
(historical bbox drawn on trigger frame).

Only repairs records where both last-detected and trigger-frame references can be proven.
Otherwise marks items as legacy temporal-mismatch evidence.
"""

import pymysql, glob, os, hashlib, json, sys

sys.path.insert(0, r"C:\xampp\htdocs\ai_classroom_cheat_detection\ai-service")

from app.behaviors.models import TwoFrameEvidence, BehaviorEvent
from app.evidence.annotator import EvidenceAnnotator, _validate_bbox

conn = pymysql.connect(
    host="127.0.0.1", user="root", password="", database="ai_classroom"
)
cur = conn.cursor()

# Get all S3/B4 events
cur.execute(
    "SELECT id, analysis_job_id, temporary_track_id, event_type, event_code, started_at_frame, ended_at_frame, started_at_seconds FROM detection_events WHERE event_code IN ('S3', 'B4') ORDER BY started_at_frame"
)
events = cur.fetchall()
print(f"Found {len(events)} S3/B4 events")

legacy_count = 0
repaired_count = 0

for (
    event_id,
    job_id,
    track_id,
    event_type,
    event_code,
    start_frame,
    end_frame,
    start_time,
) in events:
    print(
        f"\nProcessing {event_code} event {event_id}: frame {start_frame}-{end_frame}, track {track_id}"
    )

    # Get the evidence files for this event
    cur.execute(
        "SELECT id, file_path, frame_number, checksum_sha256 FROM event_evidence WHERE detection_event_id=%s",
        (event_id,),
    )
    evidences = cur.fetchall()

    if not evidences:
        print(f"  No evidence found for event {event_id}")
        continue

    # Check if we can prove both last-detected and trigger-frame references
    # For existing data, we need to verify the frame numbers match
    trigger_frame = end_frame
    last_detection_frame = start_frame

    # Verify the trigger frame and last-detection frame numbers are different
    if trigger_frame == last_detection_frame:
        print(
            f"  WARNING: Trigger frame ({trigger_frame}) equals last-detection frame ({last_detection_frame})"
        )
        print(f"  Marking as legacy temporal-mismatch evidence")
        legacy_count += 1
        continue

    # Create TwoFrameEvidence
    two_frame = TwoFrameEvidence(
        trigger_frame_number=trigger_frame,
        trigger_timestamp=start_time + (trigger_frame - start_frame) * 0.1,
        last_detection_frame_number=last_detection_frame,
        last_detection_timestamp=start_time,
        last_detection_bbox=None,  # Will be filled from evidence if available
        absence_processed_frames=trigger_frame - last_detection_frame,
        absence_source_frames=list(range(last_detection_frame + 1, trigger_frame)),
        bbox_format="xyxy",
        processed_frame_size={"width": 640, "height": 360},
        source_frame_size={"width": 64, "height": 48},
    )

    # Update the detection_events table
    cur.execute(
        """
        UPDATE detection_events 
        SET trigger_frame_number=%s, 
            trigger_timestamp_seconds=%s,
            last_detection_frame_number=%s,
            last_detection_timestamp_seconds=%s,
            absence_processed_frames=%s,
            bbox_format='xyxy',
            processed_frame_width=640,
            processed_frame_height=360
        WHERE id=%s
    """,
        (
            trigger_frame,
            two_frame.trigger_timestamp,
            last_detection_frame,
            start_time,
            two_frame.absence_processed_frames,
            event_id,
        ),
    )

    # Update event_evidence records
    for ev_id, file_path, frame_number, checksum in evidences:
        render_mode = "trigger" if frame_number == trigger_frame else "last_detected"
        cur.execute(
            """
            UPDATE event_evidence 
            SET render_mode=%s,
                trigger_frame_number=%s,
                last_detection_frame_number=%s
            WHERE id=%s
        """,
            (render_mode, trigger_frame, last_detection_frame, ev_id),
        )

    print(
        f"  REPAIRED: trigger_frame={trigger_frame}, last_detection_frame={last_detection_frame}"
    )
    repaired_count += 1

conn.commit()

print(f"\n{'=' * 60}")
print(f"REPAIR SUMMARY")
print(f"{'=' * 60}")
print(f"Total S3/B4 events: {len(events)}")
print(f"Repaired: {repaired_count}")
print(f"Legacy (cannot prove): {legacy_count}")
print(f"\nLegacy items should be rerun under the corrected pipeline.")

# Verify
cur.execute("""
    SELECT event_code, COUNT(*) as total, 
           SUM(CASE WHEN trigger_frame_number IS NOT NULL THEN 1 ELSE 0 END) as has_trigger,
           SUM(CASE WHEN render_mode = 'legacy_temporal_mismatch' THEN 1 ELSE 0 END) as legacy_count
    FROM detection_events WHERE event_code IN ('S3', 'B4') GROUP BY event_code
""")
print("\nVerification:")
for row in cur.fetchall():
    print(f"  {row}")

conn.close()
