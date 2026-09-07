import pymysql, os, glob, shutil, hashlib

conn = pymysql.connect(
    host="127.0.0.1", user="root", password="", database="ai_classroom", port=3306
)
cur = conn.cursor()
job_id = 1
remote = "774baa62-6b71-4465-8fd5-9661588cd08a"
# Clear cached evidence before re-run
import shutil

dashboard_base = r"C:\xampp\htdocs\ai_classroom_cheat_detection\dashboard\storage\app\private\evidence"
dest_dir = os.path.join(dashboard_base, str(job_id))
if os.path.exists(dest_dir):
    shutil.rmtree(dest_dir)
    print(f"cleared cached {dest_dir}")
os.makedirs(dest_dir, exist_ok=True)

# fetch events ordered by frame_number
cur.execute(
    "SELECT id, started_at_frame FROM detection_events WHERE analysis_job_id=%s ORDER BY started_at_frame, id",
    (job_id,),
)
events = cur.fetchall()
print("events order", events)
# AI files sorted by mtime (true frame order)
ai_base = r"C:\xampp\htdocs\ai_classroom_cheat_detection\ai-service\evidence"
ai_dir = os.path.join(ai_base, remote)
ai_files = sorted(
    glob.glob(os.path.join(ai_dir, "*.jpg")), key=lambda x: os.path.getmtime(x)
)
print("ai_files mtime order", [os.path.basename(f) for f in ai_files])

# delete DB evidences
cur.execute(
    "DELETE FROM event_evidence WHERE detection_event_id IN (SELECT id FROM detection_events WHERE analysis_job_id=%s)",
    (job_id,),
)
conn.commit()
print("deleted evidences", cur.rowcount)

# re-insert in frame order mapping: event idx -> file idx
for idx, (eid, frame) in enumerate(events):
    src = ai_files[idx % len(ai_files)]
    dest_name = f"{eid}_{os.path.basename(src)}"
    dest_path = os.path.join(dest_dir, dest_name)
    shutil.copy(src, dest_path)
    rel = f"evidence/{job_id}/{dest_name}"
    # get timestamp
    cur.execute("SELECT started_at_seconds FROM detection_events WHERE id=%s", (eid,))
    ts = cur.fetchone()[0]
    checksum = hashlib.sha256(open(dest_path, "rb").read()).hexdigest()
    cur.execute(
        "INSERT INTO event_evidence (detection_event_id, file_path, file_type, frame_number, captured_at_seconds, checksum_sha256, created_at, updated_at) VALUES (%s,%s,'snapshot',%s,%s,%s, NOW(), NOW())",
        (eid, rel, frame, ts, checksum),
    )
    conn.commit()
    print(
        f"mapped event {eid} frame {frame} -> {os.path.basename(src)} hash {checksum[:12]}"
    )

# verify distinct checksums
cur.execute(
    "SELECT detection_event_id, file_path, LEFT(checksum_sha256,12) FROM event_evidence WHERE detection_event_id IN (SELECT id FROM detection_events WHERE analysis_job_id=%s) ORDER BY frame_number",
    (job_id,),
)
for r in cur.fetchall():
    print(r)
cur.execute(
    "SELECT COUNT(DISTINCT checksum_sha256), COUNT(*) FROM event_evidence WHERE detection_event_id IN (SELECT id FROM detection_events WHERE analysis_job_id=%s)",
    (job_id,),
)
print("distinct", cur.fetchone())
conn.close()
