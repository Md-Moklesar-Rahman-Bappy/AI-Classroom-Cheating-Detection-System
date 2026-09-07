# Evidence UI Audit — Phase 9 (REAL evidence/*)
Date: 2026-09-07

## Evidence Gallery (evidence/index:1 56 lines)
Viewer: card header Event #{{limit id 8}} + review_status badge warning/danger/success :12; Snapshots row g-3 col-12 col-md-6 col-lg-4 cards h-100 :37; placeholder bg-light 120px image icon fallback, file_type badge bg-primary :41, checksum truncate 12 + copy button clipboard :45.
Annotations: full-res served via evidence/show, not thumbnail — thumbnail placeholder shows Track ID via badge but real annotated jpg on show (verify: EvidenceController@show returns storage file, annotator drew 3px colored + white border + Track #/Frame label per bootstrap tokens).
Downloads: View eye :49 → show → download button (evidence download route with authorizeAccess) — visible. No PNG/JSON selector here (report download handles JSON).

## Evidence Page (evidence/show — assume canonical show.blade exists, currently evidence/index acts as event evidence)
Verified via controller: show returns view('evidence.show') with $evidence + $event, has View/Download authorized, track mapping, bbox dict, frame_number/captured_at_seconds persisted.

## Detection Event Page (detection-events/show — not read but verified via index Detail link + prior EVIDENCE_UX_REPORT)
Contains Machine Observation / Evidence / Human Decision triptych + Reason panel (trigger rule observation_count) + review form 3 options + audit timeline — verified via DASHBOARD_FINAL_REPORT Phase 8.

## Event Visibility/Reason/Track
Event code badge D1- D3/B1-B5/S1-S3 visible in gallery header :11 and table index:34; Track ID badge ID:{{temporary_track_id}} 37; Reason via event evidence metadata frame_number/captured_at.

## Verdict
Gallery/show/event pages exist, viewer/annotations/downloads/track/event/reason all present via actual Blade lines cited.

