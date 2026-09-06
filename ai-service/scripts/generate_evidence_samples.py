import pathlib, cv2, numpy as np, uuid
from app.evidence.annotator import EvidenceAnnotator
from app.behaviors.models import BehaviorEvent
from app.events.models import DetectionEvent

out_dir = pathlib.Path(__file__).resolve().parents[1] / "outputs" / "evidence_samples"
out_dir.mkdir(parents=True, exist_ok=True)
ann = EvidenceAnnotator()


def blank():
    img = np.zeros((360, 640, 3), dtype=np.uint8)
    for i in range(5):
        x = 20 + i * 120
        cv2.rectangle(img, (x, 80), (x + 60, 200), (80, 80, 80), 1)
        cv2.putText(
            img,
            f"ID:{i + 1}",
            (x, 70),
            cv2.FONT_HERSHEY_SIMPLEX,
            0.4,
            (200, 200, 200),
            1,
            cv2.LINE_AA,
        )
    return img


samples = [
    ("D1", "Person Detected", "D1", 1),
    ("D2", "Mobile Phone Detected", "D2", 2),
    ("D3", "Multiple Persons Detected", "D3", 3),
    ("B1", "Looking Left", "B1", 4),
    ("B2", "Looking Right", "B2", 5),
    ("B3", "Looking Backward", "B3", 6),
    ("B4", "Possible Seat Departure", "B4", 7),
    ("B5", "Excessive Head Movement", "B5", 8),
    ("S3", "Tracking Lost", "S3", 9),
]

from app.schemas.models import BoundingBox, DetectionResult
from app.tracking.centroid_tracker import SimpleCentroidTracker

for name, label, code, tid in samples:
    frame = blank()
    bbox = {"x_min": 20 + (tid - 1) * 120, "y_min": 80, "x_max": 80 + (tid - 1) * 120, "y_max": 200}
    if code.startswith("D"):
        ev = DetectionEvent(
            event_id=str(uuid.uuid4()),
            job_id="sample",
            event_type=label,
            event_code=code,
            frame_number=42,
            timestamp_seconds=4.2,
            class_id=0,
            class_name="person",
            confidence=0.92,
            bbox=bbox,
            track_id=tid,
        )
    else:
        ev = BehaviorEvent(
            event_id=str(uuid.uuid4()),
            job_id="sample",
            track_id=tid,
            event_type=label,
            event_code=code,
            event_label=label,
            start_frame=30,
            end_frame=42,
            start_time=3.0,
            end_time=4.2,
            frame_number=42,
            timestamp_seconds=4.2,
            bbox=bbox,
            observation_count=11,
            supporting_observations=8,
            missing_observations=0,
            config_version="v2",
            method_version="geometric-v1",
            explanation="sample",
        )
        if code == "D2":
            ev.phone_bbox = {
                "x_min": bbox["x_min"] + 10,
                "y_min": bbox["y_min"] + 20,
                "x_max": bbox["x_min"] + 30,
                "y_max": bbox["y_min"] + 50,
            }
    # mock tracks for gray others
    tracker = SimpleCentroidTracker()
    dets = [
        DetectionResult(
            class_id=0,
            class_name="person",
            confidence=0.9,
            bbox=BoundingBox(x_min=20 + i * 120, y_min=80, x_max=80 + i * 120, y_max=200),
        )
        for i in range(5)
    ]
    tracks = tracker.update(dets)
    out = ann.annotate(frame, ev, tracks=tracks)
    path = out_dir / f"sample_{code}_{label.replace(' ', '_')}.jpg"
    cv2.imwrite(str(path), out)
    print(f"wrote {path}")

print("done")
