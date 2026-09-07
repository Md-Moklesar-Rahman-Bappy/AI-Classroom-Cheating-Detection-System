# DUPLICATE DOCS REPORT
Date: 2026-09-07

## 1. Versioned Superseded Docs (Highest Duplication)

| Group | Files | Canonical | Superseded | Evidence |
|---|---|---|---|---|
| Design System | DESIGN_SYSTEM.md (v1), DESIGN_SYSTEM_V2.md, DESIGN_SYSTEM_V3.md | V3 | V1, V2 | V3 header "Production-Grade Redesign 2026-08-31" supersedes; grep shows V3 referenced in dashboard, V1/V2 zero cross-refs |
| Event Taxonomy | EVENT_TAXONOMY.md vs EVENT_TAXONOMY_V2.md | V2 | V1 | V2 codes B1-B4/D2 match ai-service/app/events/taxonomy.py; V1 lacks B4 leaving_seat |
| Dashboard Workflow | DASHBOARD_WORKFLOW.md vs RECORDED_DASHBOARD_WORKFLOW.md | DASHBOARD_WORKFLOW.md | RECORDED_ subset | Recorded workflow is 60% duplicate of main (diff shows overlap) |

## 2. Remediation / Audit Duplicates (Root vs Docs)

| Root Copy | Docs Copy | Overlap | Verdict |
|---|---|---|---|
| PROJECT_REMEDIATION_REPORT.md | docs/REMEDIATION_REPORT.md + PROJECT_FINAL_REMEDIATION_REPORT.md | PROJECT_REMEDIATION is early draft (4.7KB) identical to first half of FINAL (8.9KB) | Root early is SUPERSEDED |
| DATABASE_PERSISTENCE_AUDIT.md | docs/DATABASE_IMPLEMENTATION.md | Both cover schema/migrations; root is excerpt | MERGE → DATABASE_IMPLEMENTATION |
| EVENT_COMPLIANCE_MATRIX.md | docs/EVENT_TAXONOMY_V2.md | Compliance matrix duplicates taxonomy table + codes | MERGE → TAXONOMY_V2 |
| EVIDENCE_AUDIT.md | docs/EVIDENCE_ANNOTATION_SYSTEM.md | Evidence audit duplicates annotation system | MERGE |
| PHONE_DETECTION_AUDIT.md | docs/BENCHMARK_REPORT.md | Phone audit is subset of benchmark | MERGE |
| TRACKING_FAILURE_REPORT.md | docs/TRACKING_DESIGN.md | Failure report duplicates design limitations | MERGE |
| FINAL_ACCURACY_REPORT.md | docs/FINAL_QA_REPORT.md | Accuracy metrics duplicated in QA report | MERGE |

## 3. Fix / Runtime Trace Duplicates (Temporal)

| Cluster | Files | Canonical | Superseded Traces |
|---|---|---|---|
| Video Asset Fixes | VIDEO_ASSET_ACTIONS.md + VIDEO_ASSET_CRUD_WORKFLOW.md | CRUD_WORKFLOW | VIDEO_ASSET_ACTIONS_FIX, FAILURE_ROOT_CAUSE, PAGE_RUNTIME_FIX, RUNTIME_QUERY_TRACE, SOFTDELETE_FIX (7 total, 5 are fix traces) |
| Runtime Validation | CURRENT_RUNTIME_VALIDATION.md | CURRENT_ | RECORDED_RUNTIME_VALIDATION, HTTP_CLIENT_RUNTIME_FIX, VIDEO_ASSET_RUNTIME_QUERY_TRACE |
| Full Audit | FULL_APPLICATION_AUDIT.md | CURRENT_STATE_CONSISTENCY_AUDIT | FULL_APPLICATION overlaps CURRENT_* (diff 70% identical) |

## 4. Auth/UI Duplicates (Overlap 60-80%)

| Keep | Archive |
|---|---|
| AUTH_UI_V2.md, PROFILE_UI.md, PUBLIC_LANDING_PAGE.md, DASHBOARD_ARCHITECTURE.md | AUTHENTICATION_UI.md (v1), PROFILE_PAGE_REDESIGN.md, PUBLIC_AUTH_PROFILE_RUNTIME_TRACE.md, PUBLIC_AUTH_PROFILE_VISUAL_QA.md, LANDING_PAGE_DESIGN.md |

Evidence: `diff AUTHENTICATION_UI.md AUTH_UI_V2.md` shows V2 adds password visibility toggle + strength indicator; V1 stale.

## 5. Phase Report Duplicates

PHASE_2_IMPLEMENTATION.md, PHASE_4_TEST_REPORT.md, PHASE_6_TEST_REPORT.md — all superseded by FINAL_QA_REPORT.md + FINAL_PROJECT_STATUS.md + DASHBOARD_TEST_REPORT.md. Zero source code refs (grep 0 hits beyond own file).

## 6. Generated Duplicate

- `docs/COMPLETE_PROJECT_DOCUMENTATION.md` (26 lines, markdown source) vs `AI_Classroom_Cheating_Detection_System_Complete_Documentation.docx` (75KB, thesis) — MD is source for DOCX via `scripts/generate_complete_documentation.py`. Not true duplicate; keep both but clarify. Not counted as deletable.

## 7. Duplicate Summary
- Total duplicate groups: 11
- Files involved: ~45
- Unique canonical files: ~15
- Superseded/archivable: ~30
- Byte size small (<500KB) but maintenance cost high.

## 8. Recommendation
See MARKDOWN_CLEANUP_PLAN.md: MERGE root audits into docs canonical; ARCHIVE versioned superseded (V1/V2) and fix traces; never delete thesis-critical taxonomy/evidence/RBAC.
