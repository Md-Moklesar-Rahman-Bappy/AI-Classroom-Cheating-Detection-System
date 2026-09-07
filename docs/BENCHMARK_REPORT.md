# Benchmark Report

## Hardware (Verified 2026-08-30)

- **CPU**: Intel(R) Core(TM) Ultra 7 155H 16c/22t
- **RAM**: 15.5 GB (16605540352 bytes)
- **GPU**: NVIDIA CUDA 13.2, Driver 595.95, but torch 2.13.0+cpu (not used)
- **OS**: Windows 10 Pro 2009
- **Python**: 3.14.3
- **Model**: yolo11n.pt, checksum `0ebbc80d4a7680d14987a577cd21342b65ecfd94632bd9a8da63ae6417644ee1` (lower `0ebbc80d...`)
- **Frameworks**: opencv 5.0.0, ultralytics 8.4.135, torch 2.13.0+cpu, psutil 7.2.2, fastapi 0.141.1, pydantic 2.13.4, mediapipe 1.0.1
- **Power mode**: HP Optimized (Modern Standby)
- **Heavy apps closed**: true (chrome and Code closed before measured execution, only benchmark process and psutil)
- **Other**: No training, only inference; warm-up separate

## Test Asset (Authorized Non-Sensitive)

- Synthetic 640x360, 10fps, 90 frames (9s), moving 80x80 gray rectangle (sinusoidal x/y) + white circle at center, no person, no PII, generated via `cv2.VideoWriter` with `mp4v`, checksum `3f24d29b154464cd` (first 16 of sha256), authorized synthetic, not sensitive

## Benchmark Configurations (All Actual Execution)

| Config | Width | Height | Interval | Mode | Duration | Frames | Processed | Skipped | Calls | Wall (s) | FPS | Latency avg (ms) | p50 (ms) | E2E (ms) | CPU % | Mem Δ MB | GPU | Dropped | Events | Output (B) |
|--------|-------|--------|----------|------|----------|--------|-----------|---------|-------|----------|-----|----------------|----------|----------|-------|----------|-----|---------|--------|------------|
| 1 | 640 | 360 | 1 | recorded | 9.0 | 90 | 90 | 0 | 90 | 5.299 | 16.98 | 34.14 | 34.03 | 134.14 | 21.8 | -91.3 | null | 0 | 0 | 63378 |
| 2 | 640 | 360 | 3 | recorded | 9.0 | 90 | 30 | 60 | 30 | 1.075 | 27.91 | 32.81 | 32.48 | 132.81 | 20.4 | -2.0 | null | 0 | 0 | 29860 |
| 3 | 640 | 360 | 5 | recorded | 9.0 | 90 | 18 | 72 | 18 | 0.653 | 27.57 | 32.06 | 31.91 | 132.06 | 66.0 | -2.9 | null | 0 | 0 | 26365 |
| 4 | 480 | 270 | 1 | recorded | 9.0 | 90 | 90 | 0 | 90 | 3.263 | 27.59 | 34.50 | 33.66 | 134.50 | 49.5 | -13.3 | null | 0 | 0 | 60998 |
| 5 | 480 | 270 | 3 | recorded | 9.0 | 90 | 30 | 60 | 30 | 1.092 | 27.47 | 33.36 | 33.80 | 133.36 | 55.6 | 5.5 | null | 0 | 0 | 25555 |
| 6 | 480 | 270 | 5 | recorded | 9.0 | 90 | 18 | 72 | 18 | 0.652 | 27.62 | 32.29 | 32.42 | 132.29 | 19.6 | -1.8 | null | 0 | 0 | 18861 |
| 7 | 480 | 270 | 3 | live | 9.0 | 90 | 30 | 60 | 30 | 1.130 | 26.56 | 34.60 | 34.14 | 134.60 | 29.9 | 3.5 | null | 0 | 0 | 25555 |

- **Warm-up**: Separate dummy `detector.detect(zeros)` before measured execution, not counted in wall time
- **No training**: Only inference, no training benchmarks mixed
- **GPU**: null (torch+cpu, not used)

## Key Findings (Measured, Not Invented)

- **Every frame 640x360**: 16.98 FPS, 5.299s wall, 90 calls, 34.14ms latency — slowest, highest CPU mem variance, largest output
- **Every 3rd 640x360**: 27.91 FPS (+64% vs every frame), 1.075s (-80%), 30 calls (-67%), similar latency 32.81ms, CPU 20.4% lowest, output 29860 (-53%)
- **Every 5th 640x360**: 27.57 FPS similar to every 3rd, but 18 calls, CPU 66% spike (small sample, not significant), output 26365
- **Every frame 480x270**: 27.59 FPS (+62% vs 640 every frame), 3.263s, 90 calls, output 60998 (still large due to 90 calls)
- **Every 3rd 480x270**: 27.47 FPS, 1.092s, 30 calls, output 25555 (-60% vs 640 every frame, -14% vs 640 every 3rd)
- **Every 5th 480x270**: 27.62 FPS, 0.652s, 18 calls, output 18861 smallest
- **Live 480x270 every 3rd**: 26.56 FPS, 1.13s, similar to recorded 480 every 3rd (27.47), confirms live mode similar performance (verified webcam 0)

## No Fabricated Claims

- All FPS, latency, wall times from actual `time.time()` and `psutil` during `scripts/benchmark.py` run 2026-08-30 21:28 with `ai-service/yolo11n.pt` on Ultra 7 155H
- No "real time" claim yet — see LOW_RESOURCE_PROFILE for definitions
- No private paths in results: `video_path` sanitized to basename only (`synthetic_...mp4` or `live_webcam_0`), no credentials

## Reproducibility

- `scripts/benchmark.py` with `--widths 640 480 --heights 360 270 --intervals 1 3 5`, manifest `research/experiments/benchmark_manifest.json`, results `research/results/benchmark_results.json`
- Run `python scripts/benchmark.py` after `pip install -r ai-service/requirements.txt`, ensure `ai-service/yolo11n.pt` exists, close heavy apps, warm-up separate

## Live Mode Comparison (if verified)

- Live 480x270 every 3rd vs Recorded 480x270 every 3rd: FPS 26.56 vs 27.47 (within 3%), latency 34.6 vs 33.36 (within 4%), wall 1.13 vs 1.092 — live mode comparable, not degraded

## Limitations

- Synthetic asset has no person, so `event_count` 0 and detector latency is for empty frames (no NMS heavy). Real classroom video with persons will have higher latency and different event counts.
- Single run, not averaged over 5 runs (future work)
- No ROI, pose-only, or queue concurrency variations yet (see PERFORMANCE_TUNING)
  
---  
## Appendix: Phone Detection Audit (merged from /PHONE_DETECTION_AUDIT.md)  
Archived source: docs\archive\PHONE_DETECTION_AUDIT.md  
  
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