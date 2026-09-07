import pymysql

conn = pymysql.connect(
    host="127.0.0.1", user="root", password="", database="ai_classroom", port=3306
)
cur = conn.cursor()
cur.execute(
    "SELECT id, remote_job_id, status FROM analysis_jobs ORDER BY created_at DESC LIMIT 10"
)
for r in cur.fetchall():
    print(r)
job = "d8deff47-1843-4416-a24c-e3fbd2111484"
cur.execute("SELECT id FROM analysis_jobs WHERE remote_job_id=%s", (job,))
print("found by remote_job_id", cur.fetchall())
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
# show correlation
cur.execute("SELECT id, remote_job_id, correlation_id FROM analysis_jobs")
for r in cur.fetchall():
    print("job", r)
conn.close()
