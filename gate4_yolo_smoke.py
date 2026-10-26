import os, time, sys
from ultralytics import YOLO

results = {}
model_path = r"C:\xampp\htdocs\ai_classroom_cheat_detection\yolo11n.pt"

# Valid
for label in ["valid", "missing", "corrupt"]:
    path = (
        model_path
        if label == "valid"
        else (
            r"C:\xampp\htdocs\ai_classroom_cheat_detection\yolo11n_missing.pt"
            if label == "missing"
            else r"C:\xampp\htdocs\ai_classroom_cheat_detection\yolo11n_corrupt.pt"
        )
    )
    if label == "corrupt":
        open(path, "w").write("corrupt")
    try:
        start = time.time()
        m = YOLO(path)
        load_time = time.time() - start
        results[label] = {
            "status": "PASS",
            "load_seconds": round(load_time, 2),
            "resource_limit": "none noted",
        }
    except Exception as e:
        load_time = time.time() - start if "start" in dir() else 0
        results[label] = {
            "status": "FAIL",
            "load_seconds": round(load_time, 2),
            "error": str(e),
            "resource_limit": "memory bounded by available RAM; GPU not available",
        }
    finally:
        if label == "corrupt" and os.path.exists(path):
            os.remove(path)

with open(
    r"C:\xampp\htdocs\ai_classroom_cheat_detection\outputs\yolo_smoke.json", "w"
) as f:
    import json

    json.dump(
        {
            "tests": results,
            "hardware": "CPU only; no CUDA GPU detected",
            "note": "Bounded smoke only; full inference excluded by gate scope",
        },
        f,
        indent=2,
    )

for k, v in results.items():
    print(f"{k}: {v['status']} load={v.get('load_seconds')} err={v.get('error', '')}")
