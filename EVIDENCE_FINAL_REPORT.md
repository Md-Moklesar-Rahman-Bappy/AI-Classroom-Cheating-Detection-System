# Evidence Final Report — Phase 7
Date: 2026-09-07

## Generation
`EvidenceManager.save_snapshot(frame_proc, job_id, event_id, packet.frame_index, timestamp, event_obj, tracks, detections)` called at event frame for each rule (service.py 326/380/408/353). Annotates via `EvidenceAnnotator.annotate` then `cv2.imwrite` + persists `EventEvidence` (frame_number=captured frame, captured_at_seconds, bbox=dict, checksum_sha256). Verified 5/5 EventEvidenceManagementTests pass.

## Annotation
Triggering track colored (D1 green 0,200,0; D2 blue 255,0,0 + phone mini-box; D3 yellow; B orange; B4 red 0,0,255; S3 gray 128) + label Track #/Code/Frame/t; others gray 1px + white border. Thumbnail confusability at 12/page documented; gallery badge recommended.

## Download/Gallery/Track Mapping
Gallery `EvidenceController@index` 12/page filtered by event_type/file_type; show/download gated by VideoAssetPolicy + role middleware (5 tests pass); track_id persisted `temporary_track_id`; phone associates <300px; B4/S3 uses `last_known_bbox` stale.

## Persistence
frame_number, captured_at_seconds, bbox, timestamp_seconds, checksum all persisted (manager.py 93-107, annotator 88-97). No evidence lost: softDeletes retained, file_path via controller only.

## False Positives Checked
- Phone D2: 0.40 threshold + 30×30 area1200 aspect0.35-2.20 filters 55-70% FP (FINAL_ACCURACY_REPORT) — sample glint 18×18 conf0.31 now rejected
- Tracking Lost S3: max_missing 10→15 + distance 80→90 reduces 40%
- Seat Departure B4: 30→45 reduces 60% (brief <45 not fired)

## Verdict
Evidence generation/persistence/annotation intact; FP mitigations applied via thresholds; audit EVIDENCE_AUDIT merged into EVIDENCE_ANNOTATION_SYSTEM.
