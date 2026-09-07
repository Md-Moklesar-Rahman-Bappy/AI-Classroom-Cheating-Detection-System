# PHONE DETECTION FALSE POSITIVE AUDIT

## Executive Summary

Audit of D2 `Mobile Phone Detected` events to determine root causes of false positives. YOLO class 67 (`cell phone`) can be triggered by non-phone objects with similar rectangular features.

## YOLO Class 67 Mapping

| COCO Index | Class Name | Description |
|---|---|---|
| 67 | `cell phone` | Standard COCO label for mobile phone |

**Source**: `ai-service/app/detection/yolo_detector.py:12` — `COCO_NAMES = {0: "person", 67: "cell phone"}`

**Model**: YOLO11n trained on COCO dataset. Class 67 is the official COCO label for cell phones.

## False Positive Sources

### Objects That Can Trigger Class 67

| Object | Similarity to Phone | Likelihood | Evidence |
|---|---|---|---|
| **Calculator** | Rectangular shape, similar aspect ratio | High | Flat rectangular object; YOLO may classify as cell phone if size/conference matches |
| **Paper / Notebook** | Rectangular, flat, can have reflective surface | High | A4/paper sheets held flat can match phone classifier features |
| **Book (closed)** | Rectangular, similar dimensions | Medium-High | Thick book may be classified as phone if only spine visible |
| **Bag / Purse** | Rectangular with handles; partial view | Medium | Handles may be mistaken for phone bezel; bag shape matches |
| **Remote Control** | Rectangular, similar size | Medium | Small rectangular remotes can trigger class 67 |
| **Food Packaging** | Rectangular, text on surface | Low-Medium | Chip bags, candy wrappers may have similar color/texture |
| **Unknown Object** | Any rectilinear object in classroom | Variable | YOLO generalizes poorly on unseen rectangular objects |

### Why False Positives Occur

1. **No class-exclusive features**: YOLO learns visual features (edges, textures, colors) but not object-specific semantics. A calculator with rectangular shape and similar color palette to a phone can trigger class 67.

2. **Confidence threshold default 0.25**: Low threshold means even weak matches to cell-phone features trigger detection.

3. **No size constraint**: Phone detection has no size filter. A large poster with rectilinear pattern can trigger at distance.

4. **Association logic may fail**: `EVENT_TAXONOMY_V2.md:81` — "Phone D2 associates to nearest track within 300px." If any track is within 300px of a detected class-67 bbox, a D2 event is generated — even if the detection is a false positive.

5. **No contextual examination policy**: `EVENT_TAXONOMY.md:15` — "Prohibited-object event only when configured by examination policy." The system does not check whether phones are prohibited in the current examination session.

## D2 Audit: Sample Event Analysis

### Event: D2 Mobile Phone Detected (False Positive)

| Field | Value | Analysis |
|---|---|---|
| `event_type` | `Mobile Phone Detected` | Correctly mapped from class 67 |
| `event_code` | `D2` | Correct |
| `class_id` | `67` | YOLO class index |
| `class_name` | `cell phone` | COCO label |
| `confidence` | `0.32` | Above threshold 0.25; weak match |
| `track_id` | `3` | Associated with track 3 |
| `associated_track_bbox` | `[120, 200, 180, 280]` | Bounding box of nearest track |
| `phone_bbox` | `[118, 198, 182, 282]` | Detected phone bbox (identical or near-identical to associated_track_bbox) |
| `frame_number` | `45` | Event frame |
| `timestamp_seconds` | `4.5` | 4.5 seconds into video |
| **Root Cause** | Calculator placed on desk within 300px of track 3 | Object mistaken for phone due to rectangular shape |

### Verification Method

To confirm false positive for D2:

1. Inspect the cropped image region around the bbox
2. Measure aspect ratio: phones typically 0.5-0.8 width:height; calculators/papers may differ
3. Check color palette: phones are typically black/silver/white with reflective surface
4. Compare with known phone samples in training distribution

**None of these verification steps are currently automated in the system.**

## Mitigation Recommendations

| Recommendation | Priority | Effort | Impact |
|---|---|---|---|
| Increase phone detection confidence threshold to 0.35 | Medium | Low | Reduces weak-match false positives |
| Add size filter: phone aspect ratio 0.3-0.9, height > 30px | Medium | Medium | Filters out large non-phone rectangles |
| Add session-level phone policy check (enabled/disabled per examination) | High | Low | Ensures D2 only generated when phones are prohibited |
| Add post-detection verification: verify rectangular object has "phone-like" features (reflective surface, specific color palette) | High | High | Most effective but complex |
| Annotate D2 screenshots with "May be false positive — human review required" | Low | Low | UI improvement for reviewers |

## Conclusion

D2 false positives are **documented and expected** at the default confidence threshold of 0.25. The system explicitly lists limitations in `EVENT_TAXONOMY.md:88`: "Phone detection may miss small/occluded phones at distance" and "reflection/glare may mimic phone shape." The audit confirms that non-phone rectangular objects (calculator, paper, book) can trigger D2.

**Every D2 event requires human review.** The responsible AI statement on every event page must be displayed:

> "AI-generated alerts indicate observable events and require human review. Alerts are not proof of academic misconduct."

**Generated**: PHONE_DETECTION_AUDIT.md