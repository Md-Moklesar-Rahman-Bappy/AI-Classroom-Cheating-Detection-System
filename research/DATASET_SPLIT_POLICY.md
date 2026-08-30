# Dataset Split Policy

- Split by session or participant, never by random frame.
- No same session in train/test (leakage check script verifies).
- Untouched final test set (never tuned).
- Example split: Train 70%, Validation 15%, Test 15% (proposed).
- Synthetic/non-identifiable test material only for current evaluation (real-data blocked).
