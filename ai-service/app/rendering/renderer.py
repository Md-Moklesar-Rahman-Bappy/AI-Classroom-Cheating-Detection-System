import cv2
import numpy as np

from ..behaviors.models import BehaviorEvent
from ..orientation.models import OrientationObservation
from ..schemas.models import DetectionResult

EVIDENCE_COLORS = {
    "D1": (0, 200, 0),
    "D2": (255, 0, 0),
    "D3": (0, 255, 255),
    "B1": (0, 165, 255),
    "B2": (0, 165, 255),
    "B3": (0, 165, 255),
    "B4": (0, 0, 255),
    "B5": (0, 165, 255),
    "S1": (0, 200, 0),
    "S2": (180, 180, 180),
    "S3": (128, 128, 128),
}

EVIDENCE_LABELS = {
    "D1": "D1 Person Detected",
    "D2": "D2 Mobile Phone Detected",
    "D3": "D3 Multiple Persons Detected",
    "B1": "B1 Looking Left",
    "B2": "B2 Looking Right",
    "B3": "B3 Looking Backward",
    "B4": "B4 Possible Seat Departure",
    "B5": "B5 Excessive Head Movement",
    "S1": "S1 Normal",
    "S2": "S2 Insufficient Evidence",
    "S3": "S3 Tracking Lost",
}

COLORS = {0: (0, 200, 0), 67: (255, 0, 0)}
STATE_COLORS = {
    "forward": (0, 200, 0),
    "left": (0, 165, 255),
    "right": (0, 165, 255),
    "backward": (0, 165, 255),
    "uncertain": (180, 180, 180),
    "unavailable": (120, 120, 120),
}
EVENT_COLOR = (0, 0, 255)
PHONE_COLOR = (255, 0, 0)
INSUFFICIENT_COLOR = (180, 180, 180)
ACCUMULATING_COLOR = (0, 215, 255)
OTHER_STUDENT_COLOR = (160, 160, 160)


class BoundingBoxRenderer:
    def render(
        self,
        frame: np.ndarray,
        detections: list[DetectionResult],
        tracks: list | None = None,
        observations: list[OrientationObservation] | None = None,
        behavior_events: list[BehaviorEvent] | None = None,
        evidence_mode: bool = False,
        evidence_event=None,
    ) -> np.ndarray:
        if evidence_mode and evidence_event is not None:
            from ..evidence.annotator import EvidenceAnnotator

            annotator = EvidenceAnnotator()
            return annotator.annotate(frame, evidence_event, tracks=tracks, detections=detections)
        out = frame.copy()
        active_event_tids = set()
        if behavior_events:
            for ev in behavior_events:
                active_event_tids.add(ev.track_id)

        obs_by_tid = {}
        if observations:
            for o in observations:
                obs_by_tid[o.track_id] = o

        for det in detections:
            b = det.bbox
            if det.class_id == 67:
                color = PHONE_COLOR
            else:
                color = COLORS.get(det.class_id, (200, 200, 200))
            x1, y1, x2, y2 = int(b.x_min), int(b.y_min), int(b.x_max), int(b.y_max)
            cv2.rectangle(out, (x1, y1), (x2, y2), color, 2)
            label = f"{det.class_name} {det.confidence:.2f}"
            cv2.putText(
                out,
                label,
                (x1, max(10, y1 - 6)),
                cv2.FONT_HERSHEY_SIMPLEX,
                0.5,
                color,
                1,
                cv2.LINE_AA,
            )

        if tracks:
            for tr in tracks:
                b = tr.bbox.bbox
                tid = tr.track_id
                obs = obs_by_tid.get(tid)
                if tid in active_event_tids:
                    ev = next((e for e in behavior_events if e.track_id == tid), None)
                    code = getattr(ev, "event_code", "B1") if ev else "B1"
                    color = EVIDENCE_COLORS.get(code, EVENT_COLOR)
                    state = (
                        getattr(ev, "event_label", obs.orientation_state if obs else "suspicious")
                        if ev
                        else (obs.orientation_state if obs else "suspicious")
                    )
                elif obs and obs.orientation_state in ("uncertain", "unavailable"):
                    color = INSUFFICIENT_COLOR
                    state = obs.orientation_state
                elif obs and obs.orientation_state in ("left", "right", "backward"):
                    has_event = any(ev.track_id == tid for ev in (behavior_events or []))
                    if not has_event:
                        color = ACCUMULATING_COLOR
                    else:
                        color = EVENT_COLOR
                    state = obs.orientation_state
                else:
                    color = STATE_COLORS.get(
                        obs.orientation_state if obs else "forward", (0, 200, 0)
                    )
                    state = obs.orientation_state if obs else "forward"
                x1, y1, x2, y2 = int(b.x_min), int(b.y_min), int(b.x_max), int(b.y_max)
                cv2.rectangle(out, (x1, y1), (x2, y2), color, 2)
                label = f"ID:{tid} {state}"
                if obs:
                    label += f" q:{obs.measurement_quality}"
                if tid in active_event_tids:
                    ev = next((e for e in behavior_events if e.track_id == tid), None)
                    if ev:
                        label += f" {ev.event_code} {ev.event_label}"
                cv2.putText(
                    out,
                    label,
                    (x1, max(10, y1 - 18)),
                    cv2.FONT_HERSHEY_SIMPLEX,
                    0.45,
                    color,
                    1,
                    cv2.LINE_AA,
                )
        return out

    def render_evidence(
        self,
        frame: np.ndarray,
        event,
        tracks: list | None = None,
        detections: list | None = None,
    ) -> np.ndarray:
        from ..evidence.annotator import EvidenceAnnotator

        annotator = EvidenceAnnotator()
        return annotator.annotate(frame, event, tracks=tracks, detections=detections)
