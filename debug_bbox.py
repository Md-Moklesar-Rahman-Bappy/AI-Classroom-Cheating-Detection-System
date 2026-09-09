import pymysql, glob, os, hashlib, json, sys

c = pymysql.connect(host="127.0.0.1", user="root", password="", database="ai_classroom")
cur = c.cursor()

# Find all jobs
cur.execute(
    "SELECT id, remote_job_id, status, created_at FROM analysis_jobs ORDER BY created_at DESC LIMIT 10"
)
jobs = cur.fetchall()
print("=== JOBS ===")
for j in jobs:
    print(j)

# Find B4 events
cur.execute(
    "SELECT id, analysis_job_id, temporary_track_id, event_type, started_at_frame, started_at_seconds, ended_at_frame, confidence, review_status FROM detection_events WHERE event_type='B4' ORDER BY started_at_frame"
)
b4 = cur.fetchall()
print(f"\n=== B4 EVENTS ({len(b4)}) ===")
for e in b4:
    print(e)

# Find frame 267 events
cur.execute(
    "SELECT id, analysis_job_id, temporary_track_id, event_type, started_at_frame, started_at_seconds, ended_at_frame, confidence, review_status FROM detection_events WHERE started_at_frame=267 ORDER BY event_type"
)
f267 = cur.fetchall()
print(f"\n=== FRAME 267 EVENTS ({len(f267)}) ===")
for e in f267:
    print(e)

# Check evidence for frame 267
cur.execute(
    "SELECT ee.id, ee.detection_event_id, ee.file_path, ee.frame_number, ee.captured_at_seconds, ee.width, ee.height, ee.checksum_sha256 FROM event_evidence ee JOIN detection_events de ON de.id=ee.detection_event_id WHERE ee.frame_number=267 ORDER BY de.event_type"
)
ev267 = cur.fetchall()
print(f"\n=== EVIDENCE FRAME 267 ({len(ev267)}) ===")
for e in ev267:
    print(e)

# Also check all evidence for the job with frame 267
if f267:
    job_id = f267[0][1]
    cur.execute(
        "SELECT ee.id, ee.detection_event_id, ee.file_path, ee.frame_number, ee.captured_at_seconds, ee.width, ee.height, ee.checksum_sha256 FROM event_evidence ee JOIN detection_events de ON de.id=ee.detection_event_id WHERE de.analysis_job_id=%s ORDER BY ee.frame_number",
        (job_id,),
    )
    all_ev = cur.fetchall()
    print(f"\n=== ALL EVIDENCE FOR JOB {job_id} ({len(all_ev)}) ===")
    for e in all_ev:
        print(e)

# Check detection_events for the job
if f267:
    job_id = f267[0][1]
    cur.execute(
        "SELECT id, event_type, temporary_track_id, started_at_frame, started_at_seconds, ended_at_frame, confidence, review_status FROM detection_events WHERE analysis_job_id=%s ORDER BY started_at_frame",
        (job_id,),
    )
    all_events = cur.fetchall()
    print(f"\n=== ALL EVENTS FOR JOB {job_id} ({len(all_events)}) ===")
    for e in all_events:
        print(e)

c.close()
