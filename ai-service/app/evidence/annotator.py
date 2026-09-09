import cv2
import numpy as np

COLOR_POLICY = {
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

LABEL_MAP = {
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

OTHER_STUDENT_COLOR = (160, 160, 160)


def _bbox_from_dict(bbox: dict) -> tuple[int, int, int, int]:
    return int(bbox["x_min"]), int(bbox["y_min"]), int(bbox["x_max"]), int(bbox["y_max"])


def _validate_bbox(bbox: dict | None, frame_w: int = 640, frame_h: int = 360) -> dict | None:
    if not isinstance(bbox, dict):
        return None
    try:
        x_min = float(bbox.get("x_min", 0))
        y_min = float(bbox.get("y_min", 0))
        x_max = float(bbox.get("x_max", 0))
        y_max = float(bbox.get("y_max", 0))
    except Exception:
        return None
    if x_max <= x_min or y_max <= y_min:
        return None
    if x_min < 0 or y_min < 0 or x_max < 0 or y_max < 0:
        return None
    w = x_max - x_min
    h = y_max - y_min
    if w < 10 or h < 10:
        return None
    if w * h < 500:
        return None
    if x_min > frame_w or y_min > frame_h:
        return None
    x_min = max(0, min(x_min, frame_w - 1))
    y_min = max(0, min(y_min, frame_h - 1))
    x_max = max(0, min(x_max, frame_w - 1))
    y_max = max(0, min(y_max, frame_h - 1))
    if x_max <= x_min or y_max <= y_min:
        return None
    return {
        "x_min": float(x_min),
        "y_min": float(y_min),
        "x_max": float(x_max),
        "y_max": float(y_max),
    }


def _clamp_bbox(bbox: dict, w: int, h: int) -> dict:
    v = _validate_bbox(bbox, w, h)
    return v if v is not None else bbox


def _label_for_event(event) -> tuple[str, str]:
    code = getattr(event, "event_code", None) or getattr(event, "event_type", "")
    if hasattr(event, "event_code") and event.event_code in LABEL_MAP:
        return event.event_code, LABEL_MAP[event.event_code]
    if hasattr(event, "event_type"):
        et = event.event_type
        mapping = {
            "Mobile Phone Detected": ("D2", "D2 Mobile Phone Detected"),
            "Person Detected": ("D1", "D1 Person Detected"),
            "Multiple Persons Detected": ("D3", "D3 Multiple Persons Detected"),
            "Repeated Looking Left": ("B1", "B1 Looking Left"),
            "Repeated Looking Right": ("B2", "B2 Looking Right"),
            "Looking Backward": ("B3", "B3 Looking Backward"),
            "Leaving Seat": ("B4", "B4 Possible Seat Departure"),
            "Excessive Head Movement": ("B5", "B5 Excessive Head Movement"),
            "Tracking Lost": ("S3", "S3 Tracking Lost"),
            "Insufficient Evidence": ("S2", "S2 Insufficient Evidence"),
            "Normal": ("S1", "S1 Normal"),
        }
        if et in mapping:
            return mapping[et]
    return code or "B1", code or str(getattr(event, "event_type", "Event"))


class EvidenceAnnotator:
    def annotate(
        self,
        frame: np.ndarray,
        event,
        tracks: list | None = None,
        detections: list | None = None,
        render_mode: str = "trigger",
    ) -> np.ndarray:
        """Render evidence annotation on a frame.

        render_mode:
        - "trigger": renders the trigger frame (no stale bbox for S3/B4)
        - "last_detected": renders the last-detected frame (shows full-person bbox)
        """
        out = frame.copy()
        h, w = out.shape[:2]
        code, full_label = _label_for_event(event)
        color = COLOR_POLICY.get(code, (0, 165, 255))
        bbox = getattr(event, "bbox", None)
        if bbox is None:
            bbox = getattr(event, "associated_track_bbox", None)
        bbox = _validate_bbox(bbox, w, h) if bbox is not None else None
        track_id = getattr(event, "track_id", None)
        timestamp = getattr(event, "timestamp_seconds", None)
        if timestamp is None:
            timestamp = getattr(event, "end_time", None)
            if timestamp is None:
                timestamp = getattr(event, "timestamp_seconds", 0) or 0
        frame_number = getattr(event, "frame_number", None)
        if frame_number is None:
            frame_number = getattr(event, "end_frame", 0)
        two_frame = getattr(event, "two_frame_evidence", None)
        is_s3_or_b4 = code in ("S3", "B4")
        is_last_detected = render_mode == "last_detected" and is_s3_or_b4
        is_trigger = render_mode == "trigger" and is_s3_or_b4

        if is_trigger:
            cv2.putText(
                out,
                f"Track #{track_id} {full_label}",
                (10, 20),
                cv2.FONT_HERSHEY_SIMPLEX,
                0.5,
                color,
                1,
                cv2.LINE_AA,
            )
            cv2.putText(
                out,
                "Trigger Frame - No Stale Detection",
                (10, 40),
                cv2.FONT_HERSHEY_SIMPLEX,
                0.45,
                (0, 0, 255),
                1,
                cv2.LINE_AA,
            )
            if two_frame:
                cv2.putText(
                    out,
                    f"Last Detected: Frame {two_frame.last_detection_frame_number} t={two_frame.last_detection_timestamp:.1f}s",
                    (10, 60),
                    cv2.FONT_HERSHEY_SIMPLEX,
                    0.4,
                    (0, 0, 255),
                    1,
                    cv2.LINE_AA,
                )
                cv2.putText(
                    out,
                    f"Absent for {two_frame.absence_processed_frames} processed frames",
                    (10, 80),
                    cv2.FONT_HERSHEY_SIMPLEX,
                    0.4,
                    (0, 0, 255),
                    1,
                    cv2.LINE_AA,
                )
            return out

        if is_last_detected:
            if bbox is None:
                cv2.putText(
                    out,
                    f"Track #{track_id} {full_label}",
                    (10, 20),
                    cv2.FONT_HERSHEY_SIMPLEX,
                    0.5,
                    color,
                    1,
                    cv2.LINE_AA,
                )
                cv2.putText(
                    out,
                    "Last Known Position Unavailable",
                    (10, 40),
                    cv2.FONT_HERSHEY_SIMPLEX,
                    0.45,
                    (0, 0, 255),
                    1,
                    cv2.LINE_AA,
                )
                return out
            x1, y1, x2, y2 = _bbox_from_dict(bbox)
            x1, y1 = max(0, x1), max(0, y1)
            x2, y2 = min(w - 1, x2), min(h - 1, y2)
            cv2.rectangle(out, (x1, y1), (x2, y2), color, 3)
            cv2.rectangle(out, (x1 - 1, y1 - 1), (x2 + 1, y2 + 1), (255, 255, 255), 1)
            label1 = "Last Known Position"
            label2 = full_label
            label3 = f"Track #{track_id}" if track_id is not None else "Subject"
            label4 = f"Frame {frame_number}  t={float(timestamp):.1f}s"
            labels = [label1, label2, label3, label4]
            font = cv2.FONT_HERSHEY_SIMPLEX
            scale = 0.5
            thickness = 1
            pad = 4
            sizes = [cv2.getTextSize(lbl, font, scale, thickness)[0] for lbl in labels]
            max_tw = max(s[0] for s in sizes)
            box_h = sum(s[1] for s in sizes) + pad * (len(labels) + 1)
            box_w = max_tw + pad * 2
            ly = y1 - box_h - 6
            if ly < 0:
                ly = y2 + 6
            lx = x1
            if lx + box_w > w:
                lx = max(0, w - box_w - 2)
            cv2.rectangle(out, (lx, ly), (lx + box_w, ly + box_h), color, -1)
            cv2.rectangle(out, (lx, ly), (lx + box_w, ly + box_h), (255, 255, 255), 1)
            y_cursor = ly + pad + sizes[0][1]
            for i, lbl in enumerate(labels):
                sc = 0.4 if i == len(labels) - 1 else scale
                cv2.putText(
                    out,
                    lbl,
                    (lx + pad, y_cursor),
                    font,
                    sc,
                    (255, 255, 255),
                    thickness,
                    cv2.LINE_AA,
                )
                if i < len(labels) - 1:
                    y_cursor += sizes[i][1] + pad
            return out

        if tracks is not None and len(tracks) > 0 and track_id is not None:
            for tr in tracks:
                if tr.track_id == track_id:
                    continue
                try:
                    tb = tr.bbox.bbox
                    x1, y1, x2, y2 = int(tb.x_min), int(tb.y_min), int(tb.x_max), int(tb.y_max)
                    x1, y1 = max(0, x1), max(0, y1)
                    x2, y2 = min(w - 1, x2), min(h - 1, y2)
                    if x2 > x1 and y2 > y1:
                        cv2.rectangle(out, (x1, y1), (x2, y2), OTHER_STUDENT_COLOR, 1)
                        cv2.rectangle(out, (x1, y1), (x2, y2), (80, 80, 80), 1)
                except Exception:
                    pass
        if bbox is not None:
            try:
                x1, y1, x2, y2 = _bbox_from_dict(bbox)
            except Exception:
                x1 = y1 = x2 = y2 = 0
            x1, y1 = max(0, x1), max(0, y1)
            x2, y2 = min(w - 1, x2), min(h - 1, y2)
            cv2.rectangle(out, (x1, y1), (x2, y2), color, 3)
            cv2.rectangle(out, (x1 - 1, y1 - 1), (x2 + 1, y2 + 1), (255, 255, 255), 1)
            label1 = f"Track #{track_id}" if track_id is not None else "Subject"
            if code == "D3":
                label1 = (
                    f"Track #{track_id} + others" if track_id is not None else "Multiple Persons"
                )
            label2 = full_label
            label3 = f"Frame {frame_number}  t={float(timestamp):.1f}s"
            labels = [label1, label2, label3]
            font = cv2.FONT_HERSHEY_SIMPLEX
            scale = 0.5
            thickness = 1
            pad = 4
            sizes = [cv2.getTextSize(lbl, font, scale, thickness)[0] for lbl in labels]
            max_tw = max(s[0] for s in sizes)
            box_h = sum(s[1] for s in sizes) + pad * (len(labels) + 1)
            box_w = max_tw + pad * 2
            ly = y1 - box_h - 6
            if ly < 0:
                ly = y2 + 6
            lx = x1
            if lx + box_w > w:
                lx = max(0, w - box_w - 2)
            cv2.rectangle(out, (lx, ly), (lx + box_w, ly + box_h), color, -1)
            cv2.rectangle(out, (lx, ly), (lx + box_w, ly + box_h), (255, 255, 255), 1)
            y_cursor = ly + pad + sizes[0][1]
            for i, lbl in enumerate(labels):
                cv2.putText(
                    out,
                    lbl,
                    (lx + pad, y_cursor),
                    font,
                    scale,
                    (255, 255, 255),
                    thickness,
                    cv2.LINE_AA,
                )
                if i < len(labels) - 1:
                    y_cursor += sizes[i][1] + pad
            if code == "D2":
                phone_bbox = getattr(event, "phone_bbox", None) or getattr(event, "bbox", None)
                if phone_bbox and phone_bbox is not bbox:
                    try:
                        vb = _validate_bbox(phone_bbox, w, h)
                        if vb:
                            px1, py1, px2, py2 = _bbox_from_dict(vb)
                            cv2.rectangle(out, (px1, py1), (px2, py2), COLOR_POLICY["D2"], 2)
                            cv2.putText(
                                out,
                                "Phone",
                                (px1, max(10, py1 - 4)),
                                font,
                                0.4,
                                COLOR_POLICY["D2"],
                                1,
                                cv2.LINE_AA,
                            )
                    except Exception:
                        pass
        else:
            cv2.putText(
                out,
                f"Track #{track_id} {full_label}",
                (10, 20),
                cv2.FONT_HERSHEY_SIMPLEX,
                0.5,
                color,
                1,
                cv2.LINE_AA,
            )
            cv2.putText(
                out,
                "Evidence Unavailable",
                (10, 40),
                cv2.FONT_HERSHEY_SIMPLEX,
                0.45,
                (0, 0, 255),
                1,
                cv2.LINE_AA,
            )
        return out
