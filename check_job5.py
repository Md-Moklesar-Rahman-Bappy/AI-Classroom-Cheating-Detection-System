import pymysql, glob, os, hashlib

c = pymysql.connect(host="127.0.0.1", user="root", password="", database="ai_classroom")
cur = c.cursor()
cur.execute(
    "SELECT id, remote_job_id, correlation_id, status FROM analysis_jobs WHERE id=5"
)
print(cur.fetchall())
cur.execute(
    "SELECT id, event_type, temporary_track_id, started_at_frame, started_at_seconds FROM detection_events WHERE analysis_job_id=5 ORDER BY started_at_frame"
)
rows = cur.fetchall()
print("events", len(rows))
for r in rows:
    print(r)
cur.execute(
    "SELECT ee.id, ee.detection_event_id, ee.file_path, ee.checksum_sha256, ee.frame_number FROM event_evidence ee JOIN detection_events de ON de.id=ee.detection_event_id WHERE de.analysis_job_id=5 ORDER BY de.started_at_frame"
)
evs = cur.fetchall()
print("evidences", len(evs))
for e in evs:
    print(e[0], e[1], os.path.basename(e[2]), e[3][:12] if e[3] else None, e[4])
# distinct checksums
cur.execute(
    "SELECT COUNT(DISTINCT ee.checksum_sha256), COUNT(*) FROM event_evidence ee JOIN detection_events de ON de.id=ee.detection_event_id WHERE de.analysis_job_id=5"
)
print("distinct", cur.fetchone())
# AI files
remote = "8de7e4c5-13b9-445e-9282-66aaad665488"
base = r"C:\xampp\htdocs\ai_classroom_cheat_detection\ai-service\evidence"
ai_dir = os.path.join(base, remote)
if os.path.exists(ai_dir):
    files = glob.glob(os.path.join(ai_dir, "*.jpg"))
    files_mtime = sorted(files, key=lambda x: os.path.getmtime(x))
    print("AI files", len(files))
    for f in files_mtime:
        h = hashlib.sha256(open(f, "rb").read()).hexdigest()[:12]
        print(os.path.basename(f), os.path.getsize(f), h)
else:
    print("AI dir missing", ai_dir)
# dashboard files
dash_dir = r"C:\xampp\htdocs\ai_classroom_cheat_detection\dashboard\storage\app\private\evidence\5"
if os.path.exists(dash_dir):
    dfs = glob.glob(os.path.join(dash_dir, "*.jpg"))
    print("dash files", len(dfs))
    for f in sorted(dfs):
        h = hashlib.sha256(open(f, "rb").read()).hexdigest()[:12]
        print(os.path.basename(f), os.path.getsize(f), h)
else:
    print("dash dir missing")
