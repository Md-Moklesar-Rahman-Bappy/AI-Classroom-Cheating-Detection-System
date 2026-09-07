import pymysql, os, glob, shutil, hashlib

conn = pymysql.connect(
    host="127.0.0.1", user="root", password="", database="ai_classroom", port=3306
)
cur = conn.cursor()
job_id = 1
remote = "774baa62-6b71-4465-8fd5-9661588cd08a"
# fetch events ordered
cur.execute(
    "SELECT id, event_type, temporary_track_id, started_at_frame FROM detection_events WHERE analysis_job_id=%s ORDER BY started_at_frame",
    (job_id,),
)
events = cur.fetchall()
print("events", events)
# fetch AI files sorted
ai_base = r"C:\xampp\htdocs\ai_classroom_cheat_detection\ai-service\evidence"
ai_dir = os.path.join(ai_base, remote)
ai_files = sorted(glob.glob(os.path.join(ai_dir, "*.jpg")))
print("ai_files", [os.path.basename(f) for f in ai_files])
# fetch existing evidences
cur.execute(
    "SELECT id, detection_event_id, file_path FROM event_evidence WHERE detection_event_id IN (SELECT id FROM detection_events WHERE analysis_job_id=%s)",
    (job_id,),
)
evs = cur.fetchall()
print("existing evidences", evs)
# delete dashboard files and DB rows for these events
for eid, did, fp in evs:
    full = os.path.join(
        r"C:\xampp\htdocs\ai_classroom_cheat_detection\dashboard\storage\app\private",
        fp,
    )
    if os.path.exists(full):
        os.remove(full)
        print(f"removed {full}")
cur.execute(
    "DELETE FROM event_evidence WHERE detection_event_id IN (SELECT id FROM detection_events WHERE analysis_job_id=%s)",
    (job_id,),
)
conn.commit()
print("deleted", cur.rowcount)
# re-create with distinct files
# map each event to distinct ai file in order
dashboard_base = r"C:\xampp\htdocs\ai_classroom_cheat_detection\dashboard\storage\app\private\evidence"
dest_dir = os.path.join(dashboard_base, str(job_id))
os.makedirs(dest_dir, exist_ok=True)
for idx, (eid, et, tid, frame) in enumerate(events):
    if idx < len(ai_files):
        src = ai_files[idx]  # distinct per event
    else:
        src = ai_files[idx % len(ai_files)]
    dest_name = f"{eid}_{os.path.basename(src)}"
    dest_path = os.path.join(dest_dir, dest_name)
    shutil.copy(src, dest_path)
    rel = f"evidence/{job_id}/{dest_name}"
    checksum = hashlib.sha256(open(dest_path, "rb").read()).hexdigest()
    # need frame and timestamp
    cur.execute(
        "SELECT started_at_frame, started_at_seconds FROM detection_events WHERE id=%s",
        (eid,),
    )
    fr, ts = cur.fetchone()
    cur.execute(
        "INSERT INTO event_evidence (detection_event_id, file_path, file_type, frame_number, captured_at_seconds, checksum_sha256, created_at, updated_at) VALUES (%s,%s,'snapshot',%s,%s,%s, NOW(), NOW())",
        (eid, rel, fr, ts, checksum),
    )
    conn.commit()
    print(
        f"created evidence for event {eid} from {os.path.basename(src)} -> {rel} hash {checksum[:12]}"
    )

# verify
cur.execute(
    "SELECT detection_event_id, file_path, checksum_sha256 FROM event_evidence WHERE detection_event_id IN (SELECT id FROM detection_events WHERE analysis_job_id=%s) ORDER BY detection_event_id",
    (job_id,),
)
for r in cur.fetchall():
    print(r)
conn.close()
print("repair done")
