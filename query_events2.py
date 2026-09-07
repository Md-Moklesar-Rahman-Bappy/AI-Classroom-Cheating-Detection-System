import pymysql

conn = pymysql.connect(
    host="127.0.0.1", user="root", password="", database="ai_classroom", port=3306
)
cur = conn.cursor()
cur.execute(
    "SELECT id, status, created_at FROM analysis_jobs ORDER BY created_at DESC LIMIT 10"
)
for r in cur.fetchall():
    print(r)
cur.execute("SELECT COUNT(*) FROM detection_events")
print("total events", cur.fetchone())
cur.execute("SELECT COUNT(*) FROM event_evidence")
print("total evidence", cur.fetchone())
# try that job with like
cur.execute(
    "SELECT id, event_type, temporary_track_id, started_at_frame, started_at_seconds FROM detection_events WHERE analysis_job_id LIKE '%d8deff%'"
)
print("like query", cur.fetchall())
# list columns
cur.execute("SHOW COLUMNS FROM detection_events")
for c in cur.fetchall():
    print(c)
cur.execute("SHOW COLUMNS FROM event_evidence")
for c in cur.fetchall():
    print("ev", c)
conn.close()
