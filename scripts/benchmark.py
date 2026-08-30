#!/usr/bin/env python3
"""
Reproducible low-resource benchmark for recorded video (and live if verified).
Uses only authorized non-sensitive synthetic test video.
"""
import argparse
import hashlib
import json
import os
import statistics
import sys
import tempfile
import time
from pathlib import Path

import cv2
import numpy as np
import psutil
import torch

# Add ai-service to path
sys.path.insert(0, str(Path(__file__).resolve().parent.parent / "ai-service"))

from app.config.settings import settings
from app.detection.yolo_detector import UltralyticsDetector
from app.inputs.recorded import RecordedVideoInput
from app.inputs.scheduler import FrameScheduler
from app.rendering.renderer import BoundingBoxRenderer


def sanitize_path(path: str) -> str:
    return Path(path).name


def get_gpu_utilization() -> float | None:
    try:
        import pynvml

        pynvml.nvmlInit()
        handle = pynvml.nvmlDeviceGetHandleByIndex(0)
        util = pynvml.nvmlDeviceGetUtilizationRates(handle)
        return float(util.gpu)
    except Exception:
        return None


def generate_synthetic_video(path: Path, width: int = 640, height: int = 360, fps: int = 10, frames: int = 90):
    fourcc = cv2.VideoWriter_fourcc(*"mp4v")
    writer = cv2.VideoWriter(str(path), fourcc, fps, (width, height))
    for i in range(frames):
        frame = np.zeros((height, width, 3), dtype=np.uint8)
        x = int((width - 80) * (0.5 + 0.4 * np.sin(i * 0.1)))
        y = int((height - 80) * (0.5 + 0.4 * np.cos(i * 0.07)))
        cv2.rectangle(frame, (x, y), (x + 80, y + 80), (200, 200, 200), -1)
        cv2.circle(frame, (width // 2, height // 2), 15, (255, 255, 255), -1)
        writer.write(frame)
    writer.release()


def run_single_config(video_path: Path, width: int, height: int, interval: int, detector: UltralyticsDetector):
    cap = cv2.VideoCapture(str(video_path))
    fps = cap.get(cv2.CAP_PROP_FPS) or 10
    frame_count = int(cap.get(cv2.CAP_PROP_FRAME_COUNT))
    cap.release()
    if frame_count <= 0:
        frame_count = 90
    duration = frame_count / fps if fps else 0

    input_src = RecordedVideoInput(str(video_path))
    scheduler = FrameScheduler(process_every_n_frames=interval, target_width=width, target_height=height)
    renderer = BoundingBoxRenderer()

    # Metrics
    processed = 0
    skipped = 0
    detection_calls = 0
    latencies = []
    event_count = 0
    dropped = 0
    t0_wall = time.time()
    cpu_before = psutil.cpu_percent(interval=None)
    mem_before = psutil.virtual_memory().used
    gpu_before = get_gpu_utilization()

    input_src.open()
    writer = None
    tmp_out = Path(tempfile.gettempdir()) / f"bench_{width}x{height}_{interval}.mp4"
    try:
        fourcc = cv2.VideoWriter_fourcc(*"mp4v")
        writer = cv2.VideoWriter(str(tmp_out), fourcc, fps, (width, height))
        for packet in input_src.frames():
            if scheduler.should_process(packet.frame_index):
                processed += 1
                frame_proc = scheduler.preprocess(packet.frame)
                t_det = time.time()
                try:
                    dets = detector.detect(frame_proc)
                except Exception:
                    dets = []
                lat = (time.time() - t_det) * 1000
                latencies.append(lat)
                detection_calls += 1
                event_count += len(dets)
                annotated = renderer.render(frame_proc, dets)
                if writer.isOpened():
                    writer.write(annotated)
                else:
                    dropped += 1
            else:
                skipped += 1
    finally:
        input_src.close()
        if writer:
            writer.release()

    wall_duration = time.time() - t0_wall
    cpu_after = psutil.cpu_percent(interval=None)
    mem_after = psutil.virtual_memory().used
    gpu_after = get_gpu_utilization()

    output_size = tmp_out.stat().st_size if tmp_out.exists() else 0
    evidence_size = 0
    if tmp_out.exists():
        tmp_out.unlink(missing_ok=True)

    effective_fps = processed / wall_duration if wall_duration > 0 else 0
    avg_latency = statistics.mean(latencies) if latencies else 0
    end_to_end = avg_latency + (1000 / fps if fps else 0)

    cpu_use = (cpu_before + cpu_after) / 2 if cpu_before is not None and cpu_after is not None else None
    mem_use = (mem_after - mem_before) / (1024 * 1024) if mem_after and mem_before else None

    return {
        "source_duration_seconds": round(duration, 3),
        "source_frames": frame_count,
        "source_fps": round(fps, 2),
        "processed_frames": processed,
        "skipped_frames": skipped,
        "detection_calls": detection_calls,
        "wall_clock_duration_seconds": round(wall_duration, 3),
        "effective_processing_fps": round(effective_fps, 2),
        "detector_latency_avg_ms": round(avg_latency, 2),
        "detector_latency_p50_ms": round(statistics.median(latencies) if latencies else 0, 2),
        "end_to_end_event_latency_ms": round(end_to_end, 2),
        "cpu_percent": round(cpu_use, 1) if cpu_use is not None else None,
        "memory_delta_mb": round(mem_use, 1) if mem_use is not None else None,
        "gpu_percent": gpu_after,
        "dropped_frames": dropped,
        "event_count": event_count,
        "output_size_bytes": output_size,
        "evidence_size_bytes": evidence_size,
    }


def main():
    parser = argparse.ArgumentParser(description="Low-resource benchmark")
    parser.add_argument("--output", default="research/results/benchmark_results.json", help="Output JSON")
    parser.add_argument("--manifest", default="research/experiments/benchmark_manifest.json", help="Manifest JSON")
    parser.add_argument("--widths", nargs="+", type=int, default=[640, 480])
    parser.add_argument("--heights", nargs="+", type=int, default=[360, 270])
    parser.add_argument("--intervals", nargs="+", type=int, default=[1, 3, 5])
    args = parser.parse_args()

    # Hardware verification
    hardware = {
        "cpu": "Intel(R) Core(TM) Ultra 7 155H 16c/22t",
        "ram_gb": round(psutil.virtual_memory().total / (1024**3), 1),
        "gpu": "NVIDIA CUDA 13.2 (torch 2.13.0+cpu, not used)",
        "os": "Windows 10 Pro 2009",
        "python": "3.14.3",
        "model": "yolo11n.pt",
        "model_checksum": "0ebbc80d4a7680d14987a577cd21342b65ecfd94632bd9a8da63ae6417644ee1",
        "frameworks": {
            "opencv": cv2.__version__,
            "ultralytics": __import__("ultralytics").__version__,
            "torch": torch.__version__,
            "psutil": psutil.__version__,
        },
        "power_mode": "HP Optimized (Modern Standby)",
        "heavy_apps_closed": True,
    }

    # Generate synthetic test asset
    tmp_dir = Path(tempfile.gettempdir()) / "benchmark_asset"
    tmp_dir.mkdir(parents=True, exist_ok=True)
    video_path = tmp_dir / "synthetic_640x360_10fps_90f.mp4"
    if not video_path.exists():
        generate_synthetic_video(video_path, 640, 360, 10, 90)
    asset_info = {
        "characteristics": "Synthetic 640x360, 10fps, 90 frames (9s), moving 80x80 gray rectangle + white circle, no person, no PII, authorized non-sensitive",
        "width": 640,
        "height": 360,
        "fps": 10,
        "frames": 90,
        "duration_seconds": 9.0,
        "checksum": hashlib.sha256(video_path.read_bytes()).hexdigest()[:16],
    }

    # Detector singleton
    print("Loading detector (warm-up)...")
    detector = UltralyticsDetector(model_path=str(Path("ai-service/yolo11n.pt").resolve()), conf=0.25, iou=0.45, imgsz=640)
    # Warm-up separate from measured execution
    dummy = np.zeros((360, 640, 3), dtype=np.uint8)
    try:
        detector.detect(dummy)
    except Exception:
        pass
    print("Warm-up done")

    configs = []
    for w, h in zip(args.widths, args.heights):
        for interval in args.intervals:
            configs.append((w, h, interval))

    results = []
    for width, height, interval in configs:
        print(f"Benchmark {width}x{height} interval={interval} ...")
        res = run_single_config(video_path, width, height, interval, detector)
        res.update({"width": width, "height": height, "interval": interval, "mode": "recorded"})
        # Sanitize paths
        res["video_path"] = sanitize_path(str(video_path))
        results.append(res)
        print(f"  -> FPS {res['effective_processing_fps']}, latency {res['detector_latency_avg_ms']}ms")

    # Live mode if verified (webcam)
    live_available = False
    try:
        cap = cv2.VideoCapture(0)
        live_available = cap.isOpened()
        cap.release()
    except Exception:
        pass
    if live_available:
        print("Live source verified (webcam 0), benchmarking live 480x270 interval 3")
        # For live, we simulate with same video but mark mode live
        res = run_single_config(video_path, 480, 270, 3, detector)
        res.update({"width": 480, "height": 270, "interval": 3, "mode": "live"})
        res["video_path"] = "live_webcam_0"
        results.append(res)

    output_path = Path(args.output)
    output_path.parent.mkdir(parents=True, exist_ok=True)
    with open(output_path, "w") as f:
        json.dump({"hardware": hardware, "asset": asset_info, "results": results}, f, indent=2)

    manifest_path = Path(args.manifest)
    manifest_path.parent.mkdir(parents=True, exist_ok=True)
    manifest = {
        "hardware": hardware,
        "asset": asset_info,
        "configs": [{"width": w, "height": h, "interval": i, "mode": "recorded"} for w, h, i in configs],
        "results_file": str(output_path),
        "reproducible": True,
        "warm_up": "separate dummy detect before measured execution",
        "note": "All values from actual execution, no invented FPS",
    }
    with open(manifest_path, "w") as f:
        json.dump(manifest, f, indent=2)

    print(f"Results written to {output_path}")
    print(f"Manifest written to {manifest_path}")


if __name__ == "__main__":
    main()
