# Event System Remediation Report (v2 — 11 Events)

## 1. Files Changed
- ai-service/app/events/models.py (D3, categories)
- ai-service/app/behaviors/models.py (B5,S1/S2/S3, categories)
- ai-service/app/behaviors/config.py (new thresholds v2)
- ai-service/app/behaviors/rules.py (ExcessiveHeadMovementRule, TrackingLostRule)
- ai-service/app/behaviors/engine.py (integrate B5+S3)
- ai-service/app/events/rules.py (MultiplePersonsRule, D3 creation, phone association)
- ai-service/app/events/taxonomy.py (new 11 taxonomy central)
- ai-service/app/evidence/annotator.py (D3 yellow, B5 orange, S3 gray, v2 colors)
- ai-service/app/rendering/renderer.py (v2 colors)
- ai-service/app/jobs/service.py (D3 pipeline, config propagation)
- ai-service/app/config/settings.py (v2 thresholds, version 0.4.0)
- ai-service/app/api/jobs.py (event_category, event_name, backward compat)
- ai-service/app/main.py (inject v2 config)
- dashboard/app/Models/DetectionEvent.php (EVENT_CODES, category accessor)
- dashboard/database/migrations/2026_09_06_000001_add_v2_event_taxonomy.php
- dashboard/app/Http/Controllers/DetectionEventController.php (filters category/track)
- dashboard/resources/views/detection-events/index.blade.php (11 codes, category/track filters)
- docs/* (EVENT_TAXONOMY_V2, EVENT_ENGINE_V2, TRACK_TO_EVENT_MAPPING, EVENT_CONFIGURATION_GUIDE, EVIDENCE_ANNOTATION_SYSTEM, etc.)
- ai-service/tests/test_taxonomy_v2.py (new)
- ai-service/scripts/generate_evidence_samples.py (new samples)

## 2. Architecture Changes
Single pipeline extended: D3 detection branch parallel to D2, B5 as new temporal rule, S3 as short-absence system event preceding B4. Central taxonomy module, versioned BehaviorConfig v2.

## 3. Added Event Types
- D3 Multiple Persons Detected (detection)
- B5 Excessive Head Movement (behavior)
- S3 Tracking Lost (system)
(S1/S2 already existed but now explicit in taxonomy)

## 4. Removed Event Types
None.

## 5. Detection Logic
- D3: if len(person_dets) >= threshold (2) inside optional seat_region → union bbox, cooldown 30.
- D2 unchanged (phone->nearest track 300px)
- D1 via detector

## 6. Behavioral Logic
B5 counts orientation switches (left/right/backward) within window 15, requires both left+right, switches >=4.

## 7. State Event Logic
S3: absence >=10 and <30 → Tracking Lost (gray), then B4 if still absent >=30. Cooldowns independent.

## 8. Database Changes
Migration expands detection_events.event_type ENUM to include D3,B5,S1,S2,S3 (11 values). Backward compatible (old codes remain valid).

## 9. API Changes
GET /jobs/{id}/events now returns event_code, event_name, event_label, event_category, track_id, timestamp, frame_number, bbox for all events. Backward compatible (old fields retained).

## 10. Dashboard Changes
Index: 11-code dropdown, category filter, track_id filter, category badge, time/frame columns. Show page: large annotated image with zoom, track badge, color legend.

## 11. Evidence Changes
Annotator v2: D3 yellow union box, B5 orange, S3 gray, single-subject highlight preserved. D3/B5/S3 samples generated in ai-service/outputs/evidence_samples/ (9 JPGs).

## 12. Test Coverage
- test_taxonomy_v2.py: 13 tests covering taxonomy count, colors, D3 creation/seat_region, B5 positive/negative, S3/B4 ordering, responsible AI, annotator, API category
- test_evidence_annotation.py: 11 tests (single/multi, phone, B4, bbox, manager)
- test_tracking_orientation.py: 13 existing still pass
- Total: 37+24 = 61+ tests passed

## 13. Test Results
`pytest tests/test_taxonomy_v2.py tests/test_evidence_annotation.py tests/test_tracking_orientation.py tests/test_detector.py tests/test_recorded_pipeline.py tests/test_api.py` — all passed.

## 14. Screenshots Generated
ai-service/outputs/evidence_samples/sample_D1_*.jpg through sample_S3_*.jpg (9 annotated samples showing Track #X + Event Code + Frame + timestamp with correct color per policy).

## 15. Risks
- Centroid tracker without ReID may cause ID switch affecting B5/S3.
- D3 union bbox may be large in crowded halls; seat_region config recommended.
- B5 threshold sensitive to camera FPS; tune switch threshold.

## 16. Limitations
- No facial recognition (by design).
- S1 Normal not stored as event (implicit).
- D3 seat_region manual configuration not UI-exposed yet.

## 17. Future Work
- UI for seat_region ROI editor
- ReID tracker for S3 robustness
- Short clip evidence for B5/S3

## Responsible AI Statement
AI-generated alerts indicate observable events and require human review. Alerts are not proof of academic misconduct. All labels observable only; no automatic accusation or disciplinary action. No facial recognition.

