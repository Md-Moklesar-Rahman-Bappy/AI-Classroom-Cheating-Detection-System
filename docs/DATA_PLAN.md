# Data Plan

## Recordings

- Only staged recordings with informed adult participants and supervisor/institutional approval.
- No unauthorized real exam footage. No real examination recordings without written authorization.
- Participants: adults (18+); informed consent; right to withdraw; no minors.

## Approval and Consent

- Required before collection: institutional/supervisor approval, consent form signed, data-retention notice.
- Artifacts: `CONSENT_TEMPLATE.md`, `DATA_COLLECTION_PROTOCOL.md`, `DATA_RETENTION_POLICY.md`, approval letter reference in dataset card.
- Consent covers: purpose, storage location, retention period, who can view, right to request deletion before retention expiry.

## Dataset Versioning

- Each dataset version has manifest: `dataset_version`, `created_at`, `created_by`, `hash`, `source_sessions`.
- Manifest stored alongside data; not in Git. Example: `datasets/manifests/v1.0.json`.
- Never overwrite a released dataset without version bump.

## Dataset Directory Rules

- Root `datasets/` excluded from Git (`.gitignore`).
- Structure: `datasets/raw/<session_id>/video.mp4`, `datasets/annotated/<session_id>/`, `datasets/manifests/`, `research/dataset-cards/`.
- Source code separate from datasets; model weights separate from Git; local secrets outside source control; uploaded recordings outside public web paths.

## Git Exclusion

- `.gitignore` covers: `datasets/`, `*.pt`, `*.onnx`, `weights/`, `runs/`, `evidence/`, `.env`, `*.db`, `*.sqlite3`, `__pycache__/`, `.venv/`.
- Verification: `git status` after placing file in `datasets/` shows ignored.

## Collection Metadata (per session)

- Camera position, height, approximate distance, resolution, frame rate, lighting, room layout, occlusion level, number of visible participants, event script, recording session ID, approval/consent state.
- Stored as JSON sidecar: `datasets/raw/<session_id>/metadata.json`.

## Annotation Format

- Format: COCO or YOLO txt (choose one, document). Bounding boxes: `x_center y_center width height` normalized or `x_min y_min x_max y_max`.
- Classes: 0=person, 66=cell phone (COCO IDs) plus behavior events as temporal labels separate from boxes.
- File: `datasets/annotated/<session_id>/labels/<frame>.txt` or `annotations.json`.
- Annotation guide: `research/annotation-guide/ANNOTATION_GUIDE.md`.

## Annotation Quality Control

- Two-pass: annotator + reviewer; inter-annotator agreement sampled (e.g., 10% double-annotated, Cohen's kappa reported).
- Rules include: positive example, negative example, uncertain example, start boundary, end boundary, occlusion policy, overlapping event policy, small-object policy (< 16x16 px), quality-control procedure.

## Class Definitions

- Person: visible human torso/head; occlusion > 50% marked uncertain.
- Mobile phone: handheld rectilinear device; reflection/glare ambiguous -> uncertain.
- Behavior events: temporal labels spanning frames where orientation/movement condition holds consecutively.

## Participant / Session-Based Splitting

- Split by participant, recording session, classroom setup, or video source. Never randomly split adjacent frames from same recorded event across train/test.
- Maintain untouched final test set (never used for threshold tuning).

## Leakage Prevention

- Check: no duplicate video leakage, no adjacent-frame leakage, no same-participant across splits unless explicitly documented as robustness test.
- Test: script verifies no frame from same session_id appears in both train and test manifests.

## Train / Validation / Test Plan

- Example (proposed, not measured): Train 70%, Validation 15% (threshold tuning), Test 15% (final, untouched).
- Validation used to tune: confidence threshold, minimum consecutive observations, cooldown.
- Test evaluated once before final report; no tuning on test.

## Data Retention and Secure Deletion

- Retention periods configurable: video assets 30/90 days, evidence 30/90 days, audit logs 1 year (example; institutional policy decides).
- Deletion via `retention_actions` (scheduled -> executed -> audited). Secure deletion: overwrite + unlink; verify file gone.
- Participant may request early deletion before expiry; logged as retention action.

## Dataset Card Requirement

- For every dataset version: `research/dataset-cards/DATASET_CARD.md` with license, intended use, personal-data implications, redistribution permission, class distribution report, collection protocol reference.

## Public-Release Restrictions

- Do not publicly release datasets containing identifiable persons without explicit consent for public release.
- If public release intended, de-identify (blur faces) and document; otherwise keep private.

## Synthetic-Data Labeling

- If synthetic data used (e.g., staged with props), label clearly as `synthetic: true` in manifest and dataset card; do not mix unlabeled synthetic with real in evaluation without disclosure.

## Identity Minimization

- Store temporary track IDs only; no biometric templates, no facial recognition, no identity inference.
- Evidence snapshots: no names, no student IDs; only track number and event label.

## Camera-Setting Documentation

- Per session: `camera_settings.json` with position (front/ceiling), height (m), distance (m), resolution (e.g., 1280x720), FPS, lens, lighting (lux estimate), room dimensions.
- Used for robustness analysis (compare performance by camera condition).

## Research Evaluation Datasets

- Benchmarks compare: 640x360 vs 480x270, every frame vs every 3rd vs every 5th, recorded vs live, different camera placements/lighting/occlusion where approved.
- Results exported as JSON, CSV, confusion matrix, graph-ready metrics, experiment manifest, reproduction command.
- Do not invent results; measure actual FPS, latency, CPU, memory on available laptop (i5-14500, 8 GB RAM, no GPU) before claiming performance.
