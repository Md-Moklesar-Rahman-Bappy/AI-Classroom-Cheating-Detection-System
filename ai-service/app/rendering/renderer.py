import cv2
import numpy as np

from ..behaviors.models import BehaviorEvent
from ..orientation.models import OrientationObservation
from ..schemas.models import DetectionResult

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


class BoundingBoxRenderer:
    def render(
        self,
        frame: np.ndarray,
        detections: list[DetectionResult],
        tracks: list | None = None,
        observations: list[OrientationObservation] | None = None,
        behavior_events: list[BehaviorEvent] | None = None,
    ) -> np.ndarray:
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
                    color = EVENT_COLOR
                    state = "suspicious"
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
                        label += f" {ev.event_type}"
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
