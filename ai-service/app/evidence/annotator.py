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
    ) -> np.ndarray:
        out = frame.copy()
        h, w = out.shape[:2]
        code, full_label = _label_for_event(event)
        color = COLOR_POLICY.get(code, (0, 165, 255))
        bbox = getattr(event, "bbox", None)
        if bbox is None:
            bbox = getattr(event, "associated_track_bbox", None)
        track_id = getattr(event, "track_id", None)
        timestamp = getattr(event, "timestamp_seconds", None)
        if timestamp is None:
            timestamp = getattr(event, "end_time", None)
            if timestamp is None:
                timestamp = getattr(event, "timestamp_seconds", 0) or 0
        frame_number = getattr(event, "frame_number", None)
        if frame_number is None:
            frame_number = getattr(event, "end_frame", 0)

        if tracks is not None and len(tracks) > 0 and track_id is not None:
            for tr in tracks:
                if tr.track_id == track_id:
                    continue
                tb = tr.bbox.bbox
                x1, y1, x2, y2 = int(tb.x_min), int(tb.y_min), int(tb.x_max), int(tb.y_max)
                x1, y1 = max(0, x1), max(0, y1)
                x2, y2 = min(w - 1, x2), min(h - 1, y2)
                cv2.rectangle(out, (x1, y1), (x2, y2), OTHER_STUDENT_COLOR, 1)
                cv2.rectangle(out, (x1, y1), (x2, y2), (80, 80, 80), 1)

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
            font = cv2.FONT_HERSHEY_SIMPLEX
            scale = 0.5
            thickness = 1
            pad = 4
            (tw1, th1), _ = cv2.getTextSize(label1, font, scale, thickness)
            (tw2, th2), _ = cv2.getTextSize(label2, font, scale, thickness)
            (tw3, th3), _ = cv2.getTextSize(label3, font, scale, thickness)
            max_tw = max(tw1, tw2, tw3)
            box_h = th1 + th2 + th3 + pad * 6
            box_w = max_tw + pad * 2
            ly = y1 - box_h - 6
            if ly < 0:
                ly = y2 + 6
            lx = x1
            if lx + box_w > w:
                lx = max(0, w - box_w - 2)
            cv2.rectangle(out, (lx, ly), (lx + box_w, ly + box_h), color, -1)
            cv2.rectangle(out, (lx, ly), (lx + box_w, ly + box_h), (255, 255, 255), 1)
            y_cursor = ly + pad + th1
            cv2.putText(
                out,
                label1,
                (lx + pad, y_cursor),
                font,
                scale,
                (255, 255, 255),
                thickness,
                cv2.LINE_AA,
            )
            y_cursor += th1 + pad
            cv2.putText(
                out,
                label2,
                (lx + pad, y_cursor),
                font,
                scale,
                (255, 255, 255),
                thickness,
                cv2.LINE_AA,
            )
            y_cursor += th2 + pad
            cv2.putText(
                out, label3, (lx + pad, y_cursor), font, 0.4, (255, 255, 255), 1, cv2.LINE_AA
            )

            if code == "D2":
                phone_bbox = getattr(event, "phone_bbox", None) or getattr(event, "bbox", None)
                if phone_bbox and phone_bbox is not bbox:
                    try:
                        px1, py1, px2, py2 = _bbox_from_dict(phone_bbox)
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

        return out
