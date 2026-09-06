# Event Engine V2

## Pipeline
YOLO (person/phone) -> SimpleCentroidTracker (ID) -> GeometricOrientationEstimator (left/right/backward) -> TemporalEventEngine (B1/B2/B3/B5 + S3/B4) -> Detection rules (D1/D2/D3) -> EvidenceAnnotator -> EvidenceManager

## Detection Rules
- D1: via model, not emitted as separate event unless via evidence (person is tracked)
- D2: MobilePhoneEventRule cooldown 30, phone->nearest track 300px, event_code D2
- D3: MultiplePersonsRule threshold 2, optional seat_region, union bbox, cooldown 30

## Behavior Rules
- RepeatedLookingLeft/Right/Backward: as before (window 15, min_supporting 8, max_missing 4, duration 10, cooldown 45)
- ExcessiveHeadMovementRule: counts orientation switches (left<->right or involving backward) within head_movement_window 15, needs both left and right present, switches >=4, missing <= max_missing
- LeavingSeatRule: absence >=30
- TrackingLostRule: absence >=10 and <30 (emits S3 before B4), cooldown 30

## Engine Class
`app/behaviors/engine.py:TemporalEventEngine` now holds left/right/backward/head_movement + leaving + tracking_lost + insufficient. `mark_seen` propagates to both leaving and tracking_lost. `mark_missing_tracks` emits S3 first, then B4 if not S3.

## Categories
Defined in `app/events/taxonomy.py` and `app/events/models.py:EVENT_CATEGORY_MAP`.
