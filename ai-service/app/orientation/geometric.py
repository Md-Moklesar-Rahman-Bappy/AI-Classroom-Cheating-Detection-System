from ..tracking.base import Track
from .base import OrientationEstimator
from .models import OrientationObservation


class GeometricOrientationEstimator(OrientationEstimator):
    def __init__(
        self,
        left_threshold: float = -0.15,
        right_threshold: float = 0.15,
        backward_aspect: float = 1.8,
        method_version: str = "geometric-v1",
    ):
        self.left_threshold = left_threshold
        self.right_threshold = right_threshold
        self.backward_aspect = backward_aspect
        self.method_version = method_version
        self._prev_centers: dict[int, tuple[float, float]] = {}

    def estimate(self, track: Track, timestamp: float) -> OrientationObservation:
        bbox = track.bbox.bbox
        w = bbox.x_max - bbox.x_min
        h = bbox.y_max - bbox.y_min
        if w <= 0 or h <= 0:
            return OrientationObservation(
                track_id=track.track_id,
                timestamp=timestamp,
                orientation_state="unavailable",
                measurement_quality="unavailable",
                supporting_geometry={"w": w, "h": h},
                visible_landmark_count=None,
                insufficient_reason="invalid_bbox",
                method_version=self.method_version,
            )
        cx = (bbox.x_min + bbox.x_max) / 2
        cy = (bbox.y_min + bbox.y_max) / 2
        prev = self._prev_centers.get(track.track_id)
        self._prev_centers[track.track_id] = (cx, cy)

        if w < 20 or h < 40:
            quality = "low"
        elif w < 40 or h < 80:
            quality = "medium"
        else:
            quality = "high"

        if prev is None:
            return OrientationObservation(
                track_id=track.track_id,
                timestamp=timestamp,
                orientation_state="uncertain",
                measurement_quality=quality,
                supporting_geometry={"cx": cx, "cy": cy, "w": w, "h": h, "delta": 0},
                visible_landmark_count=0,
                insufficient_reason="first_observation",
                method_version=self.method_version,
            )

        prev_cx, _ = prev
        delta_norm = (cx - prev_cx) / max(w, 1)
        aspect = h / max(w, 1)

        if aspect > self.backward_aspect:
            state = "backward"
        elif delta_norm < self.left_threshold:
            state = "left"
        elif delta_norm > self.right_threshold:
            state = "right"
        else:
            state = "forward"

        if abs(delta_norm) < 0.02 and state in ("left", "right"):
            state = "uncertain"
            reason = "insufficient_delta"
        else:
            reason = None

        if state == "uncertain" and reason is None:
            reason = "stable_forward"

        return OrientationObservation(
            track_id=track.track_id,
            timestamp=timestamp,
            orientation_state=state,
            measurement_quality=quality,
            supporting_geometry={
                "cx": cx,
                "cy": cy,
                "w": w,
                "h": h,
                "delta": delta_norm,
                "aspect": aspect,
            },
            visible_landmark_count=0,
            insufficient_reason=reason,
            method_version=self.method_version,
        )
