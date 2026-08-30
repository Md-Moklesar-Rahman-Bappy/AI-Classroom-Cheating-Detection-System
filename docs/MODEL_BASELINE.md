# Model Baseline

## Checkpoint
- `yolo11n.pt` (YOLOv11 nano), downloaded from https://github.com/ultralytics/assets/releases/download/v8.4.0/yolo11n.pt
- Size ~5.4 MB, classes 80 COCO, allowed_classes `[0 person, 67 cell phone]` only
- License AGPL-3.0 (see THIRD_PARTY_NOTICES.md, AGPL_COMPLIANCE.md)
- Not committed to Git; checksum computed at load: `0ebbc80d4a7680d14987a577cd21342b65ecfd94632bd9a8da63ae6417644ee1` (local file, verify per install)
- Load: `YOLO(model_path)` once per worker; `conf` 0.25, `iou` 0.45, `imgsz` 640 configurable

## Inference
- Input BGR frame -> YOLO predict (conf/iou/imgsz) -> filtered by allowed_classes -> returns typed `DetectionResult` (class_id, class_name, confidence, bbox xyxy)
- No raw framework objects outside detector module
- Missing model raises handled exception, endpoint returns 503, not crash
- Phone detections returned only if baseline model identifies class 67; synthetic gray-rectangle test returned 0 boxes (no false positive on non-person)

## Metadata
- `model_versions` table will store checksum, class_list, image_size, license, source_url
- Never overwrite without versioning

## Limitations (Phase 2)
- No fine-tuning; no tracking; no temporal rules; phone detection depends on COCO pretraining (may miss small/occluded phones)
- No accuracy/FPS claims; actual metrics to be measured in Phase 8

## Verification
- Import `from ultralytics import YOLO` OK on Python 3.14.0
- Inference on 640x360 zeros -> 0 boxes, no error, ~1.7s first load on CPU
