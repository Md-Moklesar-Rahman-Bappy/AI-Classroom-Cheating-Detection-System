# Audit Report — Consolidated
Date: 2026-09-07 | Sources archived in `docs/archive/` (no deletions)

This document consolidates 11 previously separate audit/accuracy reports that duplicated canonical docs. Full originals remain in `docs/archive/` for audit trail.

## 1. Database Persistence Audit
**Source**: `docs/archive/DATABASE_PERSISTENCE_AUDIT.md` → merged into `DATABASE_IMPLEMENTATION.md` appendix.
Summary: MySQL `ai_classroom` is production store; `sqlite :memory:` isolated to phpunit; `InMemory` AI-service repos intentional; `migrate:fresh` risk critical; safe migrations verified. See `DATABASE_IMPLEMENTATION.md` appendix for full table.

## 2. Event Compliance Matrix (11 Events)
**Source**: `docs/archive/EVENT_COMPLIANCE_MATRIX.md` → merged into `EVENT_TAXONOMY_V2.md` appendix.
Summary: D1/D3/B1/B2/B3/B5/S1 fully compliant; D2/B4/S3 compliant with documented false-positive risks (phone rectangular false positives, tracker stale bbox). 11-event verification per dimension (source, trigger, storage, API, dashboard, evidence, tests). See archived matrix for full 214-line verification.

## 3. Evidence Audit
**Source**: `docs/archive/EVIDENCE_AUDIT.md` → merged into `EVIDENCE_ANNOTATION_SYSTEM.md` appendix.
Summary: Screenshots captured at event frame (`packet.frame_index`), not latest frame; stacked events on same frame share base image but differ by color/label; S3/B4 stale bbox explained; bbox/timestamp persisted via `EvidenceManager.save_snapshot`; annotator highlights triggering track colored (D1 green, D2 blue, D3 yellow, B orange, B4 red, S3 gray) vs others gray 1px.

## 4. Phone Detection Audit
**Source**: `docs/archive/PHONE_DETECTION_AUDIT.md` → merged into `BENCHMARK_REPORT.md` appendix.
Summary: YOLO class 67 false positives from calculators/paper/book/bag due to rectilinear features at conf 0.25; mitigations include phone-only threshold 0.40 + size/aspect filters (see FINAL_ACCURACY_REPORT). Every D2 requires human review.

## 5. Tracking Failure Report (S3/B4)
**Source**: `docs/archive/TRACKING_FAILURE_REPORT.md` → merged into `TRACKING_DESIGN.md` appendix.
Summary: Centroid tracker `max_missing=10` + `max_distance=80` deletes track after 10 misses (~1s); S3 fires 10≤absence<30, B4 fires ≥30 with stale `last_known_bbox` even when visible; mitigations 15/45 thresholds (see FINAL_ACCURACY_REPORT).

## 6. Final Accuracy Report
**Source**: `docs/archive/FINAL_ACCURACY_REPORT.md` → merged into `FINAL_QA_REPORT.md` appendix.
Summary: Phone filters (conf 0.40, min 30×30 area 1200 aspect 0.35-2.20) reduce D2 FP 55-70%; tracker `max_missing 10→15, distance 80→90, leaving 30→45` reduces S3 40% and B4 60%; explanation panel added to `detection-events/show`.

## 7. Video Asset Fix Traces (5)
**Sources**: `docs/archive/VIDEO_ASSET_*` (ACTIONS_FIX, FAILURE_ROOT_CAUSE, PAGE_RUNTIME_FIX, RUNTIME_QUERY_TRACE, SOFTDELETE_FIX) → all in `docs/archive/` (not merged, archived).
Summary: Chronological fixes for upload/soft-delete/query traces; now superseded by `VIDEO_ASSET_CRUD_WORKFLOW.md` + `VIDEO_ASSET_ACTIONS.md`; retained for history.

## Index to Originals
All originals preserved:
- `docs/archive/DATABASE_PERSISTENCE_AUDIT.md`
- `docs/archive/EVENT_COMPLIANCE_MATRIX.md`
- `docs/archive/EVIDENCE_AUDIT.md`
- `docs/archive/PHONE_DETECTION_AUDIT.md`
- `docs/archive/TRACKING_FAILURE_REPORT.md`
- `docs/archive/FINAL_ACCURACY_REPORT.md`
- `docs/archive/VIDEO_ASSET_*` (5 files)

No file deleted; this report is a consolidated view for examiners.
