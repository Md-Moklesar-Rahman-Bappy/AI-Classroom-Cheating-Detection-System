# Reproducibility

## Commands
- `python research/evaluation/evaluate_objects.py`
- `python research/evaluation/evaluate_events.py`
- `python research/evaluation/check_split_leakage.py`
- `python research/evaluation/generate_confusion_matrix.py`

## Configuration
- Random seed: from `benchmark_manifest.json`.
- Dataset version: from `MANIFEST.md`.
- Model checksum: from `MANIFEST.md`.
- Hardware: CPU-only; `benchmark_manifest.json` records actual environment.
- Dependencies pinned: `requirements-dev.txt` / `benchmark_manifest.json`.

## Data Access
- Synthetic/non-identifiable test data only.
- Real participant evaluation blocked until consent artifacts verified.
- No weights or raw videos committed.

## Results
- Actual metrics only; no fabricated values.
- Limitations stated explicitly.
