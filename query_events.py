import pymysql

conn = pymysql.connect(
    host="127.0.0.1", user="root", password="", database="ai_classroom", port=3306
)
cur = conn.cursor()
job = "d8deff47-1843-4416-a24c-e3fbd2111484"
cur.execute(
    "SELECT id, event_type, temporary_track_id, started_at_frame, started_at_seconds, ended_at_frame, confidence, rule_score, review_status FROM detection_events WHERE analysis_job_id=%s ORDER BY started_at_frame, event_type",
    (job,),
)
rows = cur.fetchall()
print(f"Events for job {job}: {len(rows)}")
for r in rows:
    print(r)
print("\n--- evidence ---")
cur.execute(
    "SELECT id, detection_event_id, file_path, frame_number, captured_at_seconds, captured_at_frame, checksum_sha256, bbox FROM event_evidence WHERE analysis_job_id=%s ORDER BY frame_number",
    (job,),
)
rows2 = cur.fetchall()
print(f"Evidence rows: {len(rows2)}")
for r in rows2:
    print(r)
print("\n--- join ---")
cur.execute(
    """
SELECT de.id, de.event_type, de.temporary_track_id, de.started_at_frame, ee.id as ev_id, ee.file_path, ee.frame_number, ee.captured_at_seconds
FROM detection_events de
LEFT JOIN event_evidence ee ON ee.detection_event_id = de.id
WHERE de.analysis_job_id=%s
ORDER BY de.started_at_frame
""",
    (job,),
)
for r in cur.fetchall():
    print(r)
print("\n--- distinct evidence file_path count ---")
cur.execute(
    "SELECT COUNT(*), COUNT(DISTINCT file_path), COUNT(DISTINCT frame_number), COUNT(DISTINCT captured_at_seconds) FROM event_evidence WHERE analysis_job_id=%s",
    (job,),
)
print(cur.fetchone())
cur.execute(
    "SELECT file_path, COUNT(*) c, GROUP_CONCAT(detection_event_id) FROM event_evidence WHERE analysis_job_id=%s GROUP BY file_path",
    (job,),
)
print("group by file_path")
for r in cur.fetchall():
    print(r)
conn.close()
