import time

import psutil


class MetricsCollector:
    def __init__(self):
        self.start = time.time()
        self.frames_processed = 0
        self.dropped = 0

    def tick(self, processed: bool = True):
        self.frames_processed += 1
        if not processed:
            self.dropped += 1

    def snapshot(self) -> dict:
        elapsed = max(0.001, time.time() - self.start)
        fps = self.frames_processed / elapsed
        return {
            "frames_processed": self.frames_processed,
            "dropped_frames": self.dropped,
            "elapsed_seconds": elapsed,
            "processing_fps": fps,
            "cpu_percent": psutil.cpu_percent(interval=None),
            "memory_mb": psutil.virtual_memory().used // 1024 // 1024,
        }
