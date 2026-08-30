# Data Retention Policy

- Default retention: 90 days for video assets; 30 days for evidence snapshots; 1 year for audit logs.
- Configurable per institutional policy (set in dataset manifest).
- Deletion scheduled via `retention_actions` table (`scheduled` -> `executed`/`failed`).
- Secure deletion: overwrite/unlink file; verify missing; log action.
- Participant early deletion: allowed before expiry; logged.
- Real participant recordings: blocked for evaluation until full consent verified. Synthetic/non-identifiable test data only for current evaluation.
