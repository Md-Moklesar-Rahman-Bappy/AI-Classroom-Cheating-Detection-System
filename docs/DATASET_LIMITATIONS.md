# Dataset Limitations

- Real participant recordings: BLOCKED. No consent artifacts verified for any real recording.
- Synthetic/non-identifiable test data only: `synthetic: true` in manifest.
- Small dataset volume: high variance in metrics; no statistical significance claimed.
- Class balance: synthetic props may not match real exam behavior.
- No GPU: results from CPU-only environment; FPS/latency different on GPU.
- Single model family (`YOLOv11n`) evaluated; no cross-family comparison.
- No temporal model: sequential volume does not justify learning-based temporal model.
