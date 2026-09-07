# Event System Final Report — Phase 6 (11 Events)
Date: 2026-09-07

| Code | Name | Trigger | Storage ENUM | API fields | Evidence | Dashboard | Tests |
|---|---|---|---|---|---|---|---|
| D1 | Person Detected | YOLO 0 conf≥0.25 | D1 | event_code D1, bbox, track_id, frame | green person box | badge green filterable | test_detector OK |
| D2 | Mobile Phone | YOLO 67 conf≥0.40 (post-accuracy) + size/aspect filters, associate <300px | D2 | D2 +phone_bbox+associated_track_bbox | blue person+phone box | blue badge | test_detector + evidence_annotation; FP 55-70% reduced |
| D3 | Multiple Persons | ≥2 persons cooldown 30 | D3 | D3 union bbox | yellow union | yellow | taxonomy_v2 OK |
| B1 | Looking Left | left≥8 window15 ratio0.5 cooldown45 | B1 | B1 observation_count | orange | orange | tracking_orientation OK |
| B2 | Looking Right | mirrored | B2 | same | orange | orange | OK |
| B3 | Looking Back | backward≥4 ratio0.3 | B3 | same | orange | orange | OK |
| B4 | Possible Seat Departure | absence≥45 cooldown45 stale bbox (was 30) | B4 | B4 bbox last_known red | red badge | false 60% reduced per FINAL_ACCURACY_REPORT |
| B5 | Excessive Head Movement | switches≥4 covers LR | B5 | same | orange | orange | OK |
| S1 | Normal | no active events | S1 | system category | none | green | taxonomy OK |
| S2 | Insufficient Evidence | missing≤4 but <min_supporting | S2 | gray 180 | gray badge | OK |
| S3 | Tracking Lost | 15≤absence<45 (was 10) cooldown30 stale bbox | S3 | S3 gray 128 | gray | false 40% reduced |

All via behaviors/rules.py, taxonomy.py 11 codes, migration ENUM 11, DashboardController event_category mapping, tests taxonomy 13 pass + tracking_orientation pass. Dashboard shows responsible-AI notice per event. No stubs/broken.
