import os, hashlib, glob

base = r"C:\xampp\htdocs\ai_classroom_cheat_detection\ai-service\evidence\774baa62-6b71-4465-8fd5-9661588cd08a"
files = glob.glob(os.path.join(base, "*.jpg"))
for f in sorted(files):
    h = hashlib.sha256(open(f, "rb").read()).hexdigest()[:12]
    print(os.path.basename(f), os.path.getsize(f), h)
print("count", len(files))
# also check dashboard evidence for same job's AI files vs dashboard copies
print(
    "\nDashboard evidence for this job should map to 6 of these but we saw 6 dashboard files all same hash be1b33..."
)
