# Annotation Guide

## Bounding Boxes
- Format: COCO (`x_min y_min x_max y_max`) or YOLO normalized (`x_center y_center width height`).
- Small objects (< 16x16 px) marked with `uncertain` flag.
- Occlusion > 50%: `occlusion: true`, label marked `uncertain` if ambiguous.

## Event Intervals
- Temporal labels spanning consecutive frames where condition holds.
- Start/end boundaries: include first/last frame of continuous event.
- Uncertain intervals: label `uncertain` if boundary ambiguous; document reason.

## Classes
- 0 = person (visible torso/head)
- 66 = cell phone (COCO ID; handheld rectilinear device)
- Behavior events: temporal labels separate from boxes.

## Quality Control
- Two-pass: annotator + reviewer.
- Inter-annotator agreement sampled (10% double-annotated, Cohen's kappa reported).
- Reviewer identifier: non-sensitive code (e.g., `R01`, not name).
