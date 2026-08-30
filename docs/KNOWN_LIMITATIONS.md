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

## Phase 3
- InMemory repositories (no persistence across restarts); suitable for development only
- Synchronous processing (no background queue/worker); job completes within request
- No tracking/ByteTrack, no temporal behavior rules beyond phone cooldown 30
- Person detection via COCO YOLO only; not fine-tuned for classroom
- Validated on Python 3.14.3, Ultra 7 155H 16c/22t 16GB RAM, torch 2.13.0+cpu, fastapi 0.141.1; FPS not benchmarked vs low-resource profile (Phase 8)
- Evidence JPG only; no video clip 증거; retention active only
- Bearer token check is configurable but not enforced in tests beyond 401 path; hardening in Phase 9

## Phase 4
- Tracking is SimpleCentroid (80px/10 frames), not ByteTrack/DeepSORT; ID switch on crossing/occlusion >10 frames
- Orientation is geometric-v1 (bbox delta/aspect), not MediaPipe/YOLO-pose; coarse, backward via aspect proxy
- Leaving seat is proxy (track missing 30 frames), not true seat ROI; marked partially implemented
- No facial recognition, no embeddings; track IDs temporary per job
- Validated on Python 3.14.3, torch 2.13.0+cpu; geometric <1ms, YOLO 1.29s/frame CPU; no invented FPS
- Evidence best frame only, no clip yet; behavior events require_review=True

## Hardware
- 8GB RAM insufficient for large models; process-every-3 required (Phase 3 now validated on 16GB, still uses every-N)
- 512GB SSD shared for OS, models, videos, evidence; storage/ evidence dirs auto-created

## Privacy
- No facial recognition/bio storage; evidence incident-only limited to phone events pending Phase 4+
