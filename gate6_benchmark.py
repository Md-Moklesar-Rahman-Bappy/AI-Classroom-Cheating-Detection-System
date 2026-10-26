import time, psutil, os, json
from ultralytics import YOLO

m = YOLO(r"C:\xampp\htdocs\ai_classroom_cheat_detection\yolo11n.pt")
start = time.time()
proc = psutil.Process()
mem_before = proc.memory_info().rss / (1024 * 1024)
disk_before = sum(
    os.path.getsize(os.path.join(dirpath, f))
    for dirpath, _, files in os.walk(
        "C:\\xampp\\htdocs\\ai_classroom_cheat_detection\\outputs"
    )
    for f in files
)
# bounded loop
for i in range(3):
    m(
        r"C:\xampp\htdocs\ai_classroom_cheat_detection\outputs\synthetic_gate5.mp4",
        verbose=False,
        stream=True,
    )
duration = time.time() - start
mem_after = proc.memory_info().rss / (1024 * 1024)
disk_after = sum(
    os.path.getsize(os.path.join(dirpath, f))
    for dirpath, _, files in os.walk(
        "C:\\xampp\\htdocs\\ai_classroom_cheat_detection\\outputs"
    )
    for f in files
)

with open(
    r"C:\xampp\htdocs\ai_classroom_cheat_detection\outputs\gate6_benchmark.json", "w"
) as f:
    json.dump(
        {
            "hardware": "CPU (no CUDA); 1 worker bounded",
            "input": "synthetic_gate5.mp4 (10 frames, 320x240)",
            "duration_sec": round(duration, 2),
            "memory_before_mb": round(mem_before, 1),
            "memory_after_mb": round(mem_after, 1),
            "disk_growth_bytes": disk_after - disk_before,
            "queue_behavior": "No queue saturation; single-process bounded",
            "limitations": "No GPU; small synthetic input only; not production load",
            "fps_estimated": "~0.5 (bounded by model load per loop, not optimized pipeline)",
        },
        f,
        indent=2,
    )
print("Benchmark done:", duration)
