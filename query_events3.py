import pymysql

conn = pymysql.connect(
    host="127.0.0.1", user="root", password="", database="ai_classroom", port=3306
)
cur = conn.cursor()
cur.execute("SHOW COLUMNS FROM analysis_jobs")
for c in cur.fetchall():
    print(c)
cur.execute(
    "SELECT id, remote_job_id, remote_task_id, status, created_at FROM analysis_jobs ORDER BY created_at DESC LIMIT 10"
)
for r in cur.fetchall():
    print(r)
cur.execute(
    "SELECT id, event_type, temporary_track_id, started_at_frame, started_at_seconds, analysis_job_id FROM detection_events ORDER BY started_at_frame"
)
for r in cur.fetchall():
    print("event", r)
cur.execute(
    "SELECT id, detection_event_id, file_path, frame_number, captured_at_seconds FROM event_evidence"
)
for r in cur.fetchall():
    print("ev", r)
conn.close()
