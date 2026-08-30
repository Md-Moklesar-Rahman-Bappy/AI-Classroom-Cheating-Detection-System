# Dataset Versioning

- Each version: `v{major}.{minor}.{patch}` with manifest (`MANIFEST.md`).
- Manifest fields: `dataset_version`, `created_at`, `created_by`, `hash` (SHA-256 of combined annotations), `source_sessions`, `approval_reference`, `retention_date`, `license`, `synthetic` flag.
- Never overwrite; always version bump.
- Real participant data: blocked for evaluation until full consent artifacts present.
- Synthetic/non-identifiable data: labeled `synthetic: true` in manifest.
