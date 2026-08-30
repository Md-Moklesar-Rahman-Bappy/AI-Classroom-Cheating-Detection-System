# Known Limitations

## Phase 2
- No tracking (ByteTrack/DeepSORT in Phase 4)
- No behavior events (looking left/right/back, leaving seat) and no temporal rules until Phase 4
- Phone detection only if COCO pretrained YOLO identifies class 67; not fine-tuned
- No Laravel dashboard (Phase 5)
- No GPU; CPU-only inference on i5-14500, 8GB RAM; FPS not yet benchmarked
- RTSP input is placeholder with validation but not tested against EZVIZ CP1 Lite (unverified stream capability)
- Debug endpoint `/debug/analyze-local` dev-only, restricted roots; not a production API
- Pydantic class-based Config and FastAPI on_event deprecation warnings (non-blocking, to be migrated in Phase 3)
- Model checksum `0ebbc80d...` is local; may vary per download; verify per install

## Hardware
- 8GB RAM insufficient for large models; process-every-3 required
- 512GB SSD shared for OS, models, videos, evidence

## Privacy
- No facial recognition/bio storage; evidence incident-only pending Phase 4+
