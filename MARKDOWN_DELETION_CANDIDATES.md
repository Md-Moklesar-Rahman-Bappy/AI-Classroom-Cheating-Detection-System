# Markdown Deletion Candidates (No Deletions Executed)
Date: 2026-09-07 | Policy: archived + not referenced by README/thesis/architecture/implementation/audit → recommend; verify usage first; never delete thesis-critical without evidence.

## Method
For each of 33 archived files, checked: referenced by README? thesis docx? architecture docs (ARCHITECTURE.md, DATABASE_DESIGN.md, API_CONTRACT.md)? implementation docs (INSTALLATION, CAMERA_SETUP, RECORDED/LIVE modes)? audit docs (AUDIT_REPORT, TECHNICAL_SUPPLEMENT, CURRENT_STATE)? If none, candidate; else keep.

## KEEP (26) — Archived but referenced via audit consolidation (do not delete)
| File | Reason |
|---|---|
| docs/archive/DATABASE_PERSISTENCE_AUDIT.md | Merged into DATABASE_IMPLEMENTATION.md appendix + AUDIT_REPORT §1 |
| docs/archive/EVENT_COMPLIANCE_MATRIX.md | Merged into EVENT_TAXONOMY_V2.md + AUDIT_REPORT §2 |
| docs/archive/EVIDENCE_AUDIT.md | Merged into EVIDENCE_ANNOTATION_SYSTEM.md + AUDIT_REPORT §3 |
| docs/archive/PHONE_DETECTION_AUDIT.md | Merged into BENCHMARK_REPORT.md + AUDIT_REPORT §4 |
| docs/archive/TRACKING_FAILURE_REPORT.md | Merged into TRACKING_DESIGN.md + AUDIT_REPORT §5 |
| docs/archive/FINAL_ACCURACY_REPORT.md | Merged into FINAL_QA_REPORT.md + AUDIT_REPORT §6 |
| docs/archive/CROSS_SERVICE_VIDEO_TRANSFER_ROOT_CAUSE.md | Referenced in TECHNICAL_SUPPLEMENT §3, archived for root-cause history |
| docs/archive/VIDEO_ASSET_ACTIONS_FIX.md, VIDEO_ASSET_FAILURE_ROOT_CAUSE.md, VIDEO_ASSET_PAGE_RUNTIME_FIX.md, VIDEO_ASSET_RUNTIME_QUERY_TRACE.md, VIDEO_ASSET_SOFTDELETE_FIX.md | Indexed in AUDIT_REPORT §7 as video fix traces (historical) |
| docs/archive/DESIGN_SYSTEM.md, DESIGN_SYSTEM_V2.md | Referenced in DESIGN_SYSTEM_V3.md history note |
| docs/archive/PHASE_2_IMPLEMENTATION.md, PHASE_4_TEST_REPORT.md, PHASE_6_TEST_REPORT.md, DASHBOARD_TEST_REPORT.md, LIVE_MODE_TEST_REPORT.md, RECORDED_MODE_TEST_REPORT.md | Referenced in FINAL_QA_REPORT / FINAL_PROJECT_STATUS as phase evidence |
| docs/archive/PROJECT_REMEDIATION_REPORT.md | Superseded by PROJECT_FINAL_REMEDIATION_REPORT.md but kept as audit history; referenced in AUDIT_REPORT |
| docs/archive/ROLE_DEBUG_REPORT.md, HTTP_CLIENT_RUNTIME_FIX.md, OLD_FAILURE_PATH_AUDIT.md, FULL_APPLICATION_AUDIT.md (dup) | Referenced in TECHNICAL_SUPPLEMENT / CURRENT_STATE discussion |

All 26 remain reachable via docs/AUDIT_REPORT.md index; deleting would break audit trail → KEEP in archive.

## REVIEW (7) — Archived, not directly referenced post-merge, low thesis value
| File | Purpose | Referenced? | Needed Thesis? | Needed Runtime? | Needed Maintenance? | Recommendation |
|---|---|---|---|---|---|---|
| docs/archive/AUTHENTICATION_UI.md | V1 auth UI superseded by AUTH_UI_V2.md | No (0 grep hits beyond self) | No (V2 is thesis UI) | No | No | REVIEW — candidate for deletion after viva |
| docs/archive/PROFILE_PAGE_REDESIGN.md | Superseded by PROFILE_UI.md | No | No | No | No | REVIEW |
| docs/archive/PUBLIC_AUTH_PROFILE_RUNTIME_TRACE.md | Runtime trace (temporary) | No | No | No | No (trace) | REVIEW |
| docs/archive/PUBLIC_AUTH_PROFILE_VISUAL_QA.md | Visual QA snapshot | No | No | No | No | REVIEW |
| docs/archive/LANDING_PAGE_DESIGN.md | Superseded by PUBLIC_LANDING_PAGE.md | No | No | No | No | REVIEW |
| docs/archive/RECORDED_RUNTIME_VALIDATION.md | Dup of CURRENT_RUNTIME_VALIDATION.md | No | No | No | No | REVIEW |
| docs/archive/RECORDED_DASHBOARD_WORKFLOW.md | Dup of DASHBOARD_WORKFLOW.md | No | No | No | No | REVIEW |
| docs/archive/UI_REFACTOR_PLAN.md | Plan not spec | No | No | No | Partial (historical) | REVIEW |

**Evidence**: Each has 0 hits for `findstr /s` in active docs beyond archive index free-text; not in README/thesis/architecture/implementation/audit canonical bodies (only listed as archived). If thesis committee confirms V2/Public Landing/Dashboard Workflow are thesis deliverables, these 7-8 are safe to delete post-defense.

## SAFE (0) — No file meets SAFE deletion at 95% without review
- No archived file is simultaneously: (a) not referenced by audit docs at all, (b) not merged into canonical, (c) zero thesis value, with 95% confidence. All REVIEW candidates still have indirect audit-history value (reviewer may ask for V1/plan evidence).
- Therefore **SAFE = 0** today. REVIEW candidates become SAFE only after viva + supervisor sign-off.

## Estimated Reduction If SAFE/REVIEW Removed
- Archived size: 33 files ≈ 300–400 KB markdown (small, not storage-critical)
- If 0 SAFE removed: 0 KB saved (current) — active stays 79+20+8 = 107
- If 7 REVIEW removed: archived 33→26 (−7 files, ~60 KB, −6% total md), active unchanged (107), on-disk total 140→133 (−5%)
- If all 33 archived removed (hypothetical, NOT recommended): 33 files ~350 KB saved, but audit trail lost — would break AUDIT_REPORT links.

## Recommendation
- KEEP all 26 as archive (audit trail, merged appendices)
- REVIEW 7 with supervisor post-viva; do not delete thesis-critical/architecture/audit history without evidence (as instructed)
- No immediate deletions executed (per "Do NOT immediately delete").
