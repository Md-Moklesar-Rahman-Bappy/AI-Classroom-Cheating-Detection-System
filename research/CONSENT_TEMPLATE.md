# Consent Template — Classroom Video Recording Study

## Institutional / Supervisor Approval Reference
- Study protocol approved per DATA_PLAN.md.
- Supervisor/institution sign-off required before any recording.
- Approval reference stored in dataset card (`approval_reference`).

## Participant Information (Adults 18+ Only)
- No minors.
- Voluntary participation; right to withdraw at any time.
- Withdrawal can occur before retention expiry; deletion logged.

## What Will Be Recorded
- Staged exam-room video only (synthetic/non-identifiable props or authorized adult participants with consent).
- No identifiable student IDs, names, or biometric templates.
- Temporary track IDs only.

## Data Collected
- Video resolution, frame rate, camera angle, lighting, duration.
- Event annotations (bounding boxes, temporal intervals).
- No participant names in manifest or annotations.

## Storage and Access
- Raw video: local `datasets/raw/<session_id>/` (excluded from Git).
- Manifest and annotations: `datasets/manifests/` / `datasets/annotated/`.
- Access limited to authorized researchers listed in dataset card.

## Retention and Deletion
- Default retention: 90 days (configurable per institutional policy).
- Secure deletion: file overwritten/unlinked; verified missing; logged in `retention_actions`.
- Early deletion upon request before expiry; logged.

## Consent Checkboxes
- [ ] I have read the information sheet.
- [ ] I understand the purpose (behavior detection research, synthetic/non-identifiable use).
- [ ] I consent to video recording for this study.
- [ ] I understand I may withdraw and request deletion.
- [ ] I confirm I am 18 years or older.

## Signature
- Name (temporary, not stored in manifest): _______________
- Signature: _______________
- Date: _______________
- Approval reference: DATA_PLAN.md approved; no unauthorized real exam footage.
