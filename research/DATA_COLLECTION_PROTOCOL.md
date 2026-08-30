# Data Collection Protocol

## Scope
Staged recordings only; synthetic/non-identifiable test material authorized.
No real exam footage without separate explicit authorization.

## Pre-Collection Approval
1. Institutional/supervisor approval letter (reference in DATA_PLAN.md).
2. Signed consent from all adult participants (CONSENT_TEMPLATE.md).
3. Data retention period set (default 90 days).

## Collection Setup
- Camera: fixed or ceiling-mounted; position/height/distance recorded.
- Resolution: documented (e.g., 640x360, 1280x720, 480x270).
- Frame rate: 10 fps (benchmark); configurable.
- Lighting: approximate lux estimate; note changes.
- Occlusion level: none / partial / heavy (documented).
- Number of visible participants: count.
- Scenario/script: event script recorded per session.

## Security During Collection
- No public web paths for raw recordings.
- Temporary storage only; moved to `datasets/raw/<session_id>/`.
- No biometric templates; temporary track IDs only.

## Post-Collection
- Metadata JSON (`metadata.json`) per session: camera settings, approval/consent reference, scenario.
- Manifest entry created (`MANIFEST.md`) before annotation.
- Real-data evaluation blocked until full approval and consent artifacts verified.
