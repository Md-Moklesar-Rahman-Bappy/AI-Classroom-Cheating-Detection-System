# Research Evaluation Report

## Status
- Real-data evaluation: BLOCKED (no consent artifacts verified for real recordings).
- Synthetic/non-identifiable test evaluation: ACTIVE.
- Dataset version: `v0.1.0-synthetic`.

## Actual Metrics (Synthetic Only)
- Per-class precision/recall/F1: reported in `research/results/benchmark_results.json`.
- Confusion matrix: `research/results/confusion_matrix.json`.
- Processing FPS, latency, CPU/memory: `benchmark_manifest.json` (actual measurements, no fabricated values).
- False-positive rate, false-negative rate: reported if measured; otherwise stated as "not measured".

## Baseline Experiments
- A. Pretrained object-detection baseline (`yolo11n.pt`).
- B. Fine-tuned object detector: ONLY if sufficient authorized synthetic/non-identifiable data exists; real-data blocked.
- C. Detector plus orientation.
- D. Detector plus tracking plus temporal rules.
- Temporal model: only if sequential data volume justifies it (currently synthetic/test only; volume limited).

## Robustness Grouping
- Lighting, camera angle, resolution, distance, occlusion, number of visible participants, recorded vs live.
- Only groups with actual synthetic/test data are reported; missing groups stated explicitly.

## Limitations (Explicit)
- No real participant recordings included (blocked).
- Synthetic data only for current evaluation.
- Class balance may not reflect real exam conditions.
- Small dataset volume; metrics have high variance.
- No GPU used; results from CPU-only environment.

## Reproducibility
- See `REPRODUCIBILITY.md`.
- Dataset manifest: `MANIFEST.md`.
- Random seed, framework versions, hardware: `benchmark_manifest.json`.
