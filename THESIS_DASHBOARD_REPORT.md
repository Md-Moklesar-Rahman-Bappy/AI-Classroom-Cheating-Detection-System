# Thesis Dashboard Report — Phase 11
Date: 2026-09-07

## Human Review Required
Every detection-events/show and evidence page shows "AI-generated alerts indicate observable events and require human review. Alerts are not proof of academic misconduct." Every review form offers confirmed/dismissed/needs_review + human decision timeline.

## No Automatic Accusation
No view uses "cheater/fraud/guilty" labels; taxonomy D/B/S are observable (Looking Left, Phone Detected). ReviewDecision required to confirm.

## No Facial Recognition
No facial data, only person bbox (class 0) + track_id; no identity, no face landmark, verified via evidence annotator only bbox.

## Responsible AI Notice
Sticky AI Notice in bootstrap layout + footer on every page (offcanvas sidebar, topbar badge).

## Reviewer Workflow
Invigilator → detection-events index → show (Machine Observation/Evidence/Human Decision) → evidence download → review form → ReviewDecision stored + AuditLog → auditor report view. Verified via RecordedWorkflowTest 16/16.

## Verdict
Thesis UX fully compliant for viva.
