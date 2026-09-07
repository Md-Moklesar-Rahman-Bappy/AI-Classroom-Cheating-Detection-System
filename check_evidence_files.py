import os, hashlib

base = r"C:\xampp\htdocs\ai_classroom_cheat_detection\dashboard\storage\app\private\evidence\1"
files = [f for f in os.listdir(base) if "774baa62" in f]
for f in sorted(files):
    p = os.path.join(base, f)
    sz = os.path.getsize(p)
    h = hashlib.sha256(open(p, "rb").read()).hexdigest()[:12]
    print(f, sz, h)

# also check raw frame numbers via python maybe via image difference? just size/hash
