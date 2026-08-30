import argparse
import json
import sys
from pathlib import Path

if __package__ in (None, ""):
    import pathlib as _pl

    sys.path.insert(0, str(_pl.Path(__file__).resolve().parents[1]))

try:
    from .detection.yolo_detector import UltralyticsDetector
    from .events.repository import InMemoryEventRepository
    from .evidence.manager import EvidenceManager
    from .jobs.repository import InMemoryJobRepository
    from .jobs.service import RecordedAnalysisService
except ImportError:
    from app.detection.yolo_detector import UltralyticsDetector
    from app.events.repository import InMemoryEventRepository
    from app.evidence.manager import EvidenceManager
    from app.jobs.repository import InMemoryJobRepository
    from app.jobs.service import RecordedAnalysisService


def parse_args(argv=None):
    p = argparse.ArgumentParser(description="Recorded video analysis CLI")
    p.add_argument("--input", required=True, help="Input video path")
    p.add_argument("--output-dir", default="outputs", help="Output directory")
    p.add_argument("--storage-dir", default="storage", help="Stored uploads directory")
    p.add_argument("--evidence-dir", default="evidence", help="Evidence directory")
    p.add_argument("--model-path", default="yolo11n.pt", help="Model weights path")
    p.add_argument("--imgsz", type=int, default=640, help="Model image size")
    p.add_argument("--frame-interval", type=int, default=3, help="Process every N frames")
    p.add_argument("--conf", type=float, default=0.25, help="Confidence threshold")
    p.add_argument("--iou", type=float, default=0.45, help="IOU threshold")
    p.add_argument("--device", default="cpu", help="Device (cpu only in Phase 3)")
    p.add_argument("--enable-evidence", action="store_true", default=True, help="Enable evidence")
    p.add_argument("--disable-evidence", action="store_true", help="Disable evidence")
    p.add_argument("--cooldown", type=int, default=30, help="Event cooldown frames")
    p.add_argument("--json", action="store_true", help="JSON summary output")
    args = p.parse_args(argv)
    if args.frame_interval < 1:
        p.error("frame-interval must be >=1")
    if not (0 < args.conf <= 1):
        p.error("conf must be 0-1")
    if not (0 < args.iou <= 1):
        p.error("iou must be 0-1")
    if args.imgsz < 32:
        p.error("imgsz too small")
    if args.disable_evidence:
        args.enable_evidence = False
    inp = Path(args.input)
    if not inp.exists():
        p.error(f"Input not found: {inp}")
    if ".." in str(inp):
        p.error("Invalid input path")
    return args


def main(argv=None):
    args = parse_args(argv)
    detector = UltralyticsDetector(
        model_path=args.model_path,
        conf=args.conf,
        iou=args.iou,
        imgsz=args.imgsz,
    )
    job_repo = InMemoryJobRepository()
    event_repo = InMemoryEventRepository()
    evidence_mgr = EvidenceManager(args.evidence_dir, enabled=args.enable_evidence)
    service = RecordedAnalysisService(
        job_repo=job_repo,
        event_repo=event_repo,
        evidence_manager=evidence_mgr,
        detector=detector,
        storage_dir=args.storage_dir,
        output_dir=args.output_dir,
        event_cooldown_frames=args.cooldown,
    )
    job = service.create_job_from_existing(args.input)
    job = service.process(
        job.job_id,
        process_every_n_frames=args.frame_interval,
        target_width=args.imgsz,
        target_height=int(args.imgsz * 9 / 16) if args.imgsz == 640 else args.imgsz,
        evidence_enabled=args.enable_evidence,
    )
    summary = {
        "job_id": job.job_id,
        "status": job.status.value,
        "input": str(args.input),
        "output": job.output_path,
        "output_metadata": job.output_metadata,
        "metrics": job.metrics,
        "events": len(event_repo.list_by_job(job.job_id)),
        "failure_reason": job.failure_reason,
    }
    if args.json:
        print(json.dumps(summary, indent=2))
    else:
        print(f"Job {job.job_id} {job.status.value}")
        print(f"Output: {job.output_path}")
        print(f"Events: {summary['events']}")
        print(f"Metrics: {json.dumps(job.metrics, indent=2)}")
        if job.failure_reason:
            print(f"Failed: {job.failure_reason}", file=sys.stderr)
            sys.exit(1)
    return summary


if __name__ == "__main__":
    main()
