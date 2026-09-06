# Event Taxonomy V2 (11 Events)

## Summary
TOTAL 11 EVENT TYPES. No label uses cheater/fraud. All require human review.

## Detection (Category: detection)
| Code | Name | Trigger | BBox | Color |
|------|------|---------|------|-------|
| D1 | Person Detected | YOLO class 0 confidence >= threshold | person bbox | Green (0,200,0) |
| D2 | Mobile Phone Detected | YOLO class 67, associated to nearest track <300px | track bbox or phone bbox | Blue (255,0,0) |
| D3 | Multiple Persons Detected | >=2 persons in frame (or inside seat_region) with cooldown 30 | union bbox of persons | Yellow (0,255,255) |

## Behavior (Category: behavior)
| Code | Name | Trigger | Color |
|------|------|---------|-------|
| B1 | Repeated Looking Left | left count >= min_supporting, window 15, ratio 0.5, cooldown 45 | Orange |
| B2 | Repeated Looking Right | right count >= min_supporting | Orange |
| B3 | Looking Backward | backward >= max(3, min_supporting//2), ratio 0.3 | Orange |
| B4 | Possible Seat Departure | absence >= leaving_absence_frames 30 | Red (0,0,255) |
| B5 | Excessive Head Movement | switches left/right/backward >=4 within window 15, covers both left+right | Orange |

## System (Category: system)
| Code | Name | Trigger | Color |
|------|------|---------|-------|
| S1 | Normal | no active behavior/detection events | Green |
| S2 | Insufficient Evidence | uncertain/unavailable or low quality | Gray (180,180,180) |
| S3 | Tracking Lost | absence >=10 and <30, cooldown 30 (before B4) | Gray (128,128,128) |

## Responsible AI
Every page shows: "AI-generated alerts indicate observable events and require human review. Alerts are not proof of academic misconduct."

## API Fields
Each event exposes: event_code, event_name, event_label, event_category, track_id, timestamp, frame_number, bbox.
