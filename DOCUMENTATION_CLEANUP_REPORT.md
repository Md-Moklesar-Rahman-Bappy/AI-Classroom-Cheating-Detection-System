# DOCUMENTATION CLEANUP REPORT
Date: 2026-09-07

## Inventory
- docs/: 104 markdown files
- research/: 8 markdown files
- root: README.md, CHANGELOG.md, 8 audit .md, .docx thesis bundle

## 1. Duplicate / Versioned Docs (Highest Savings)

| Group | Files | Issue | Recommendation |
|---|---|---|---|
| Design System | DESIGN_SYSTEM.md, DESIGN_SYSTEM_V2.md, DESIGN_SYSTEM_V3.md | V3 supersedes V2 and V1; V1/V2 stale | Keep V3 only, archive V1/V2 to docs/audit/ or delete |
| Event Taxonomy | EVENT_TAXONOMY.md vs EVENT_TAXONOMY_V2.md | V2 is canonical (code uses V2 codes B1/B2/D2) | Keep V2, archive V1 |
| Event System | EVENT_SYSTEM_V2.md, EVENT_ENGINE_V2.md, EVENT_SYSTEM_REMEDIATION_REPORT.md | Overlapping; V2 canonical | Merge or keep V2 + remediation as history |
| Auth/Profile | AUTHENTICATION_UI.md, AUTH_UI_V2.md, PROFILE_UI.md, PROFILE_PAGE_REDESIGN.md, PUBLIC_AUTH_PROFILE_* (2) | 6 files cover same flow | Keep AUTH_UI_V2 + PROFILE_UI, archive rest |
| Video Asset | VIDEO_ASSET_ACTIONS.md, VIDEO_ASSET_ACTIONS_FIX.md, VIDEO_ASSET_CRUD_WORKFLOW.md, VIDEO_ASSET_FAILURE_ROOT_CAUSE.md, VIDEO_ASSET_PAGE_RUNTIME_FIX.md, VIDEO_ASSET_RUNTIME_QUERY_TRACE.md, VIDEO_ASSET_SOFTDELETE_FIX.md | 7 files, 4 are fix traces (temporal) | Keep CRUD_WORKFLOW + ACTIONS, archive FIX/TRACE |
| Remediation | REMEDIATION_REPORT.md, docs/audit/*.md, root PROJECT_REMEDIATION_REPORT.md, PROJECT_FINAL_REMEDIATION_REPORT.md, CURRENT_STATE_CONSISTENCY_AUDIT.md, FULL_APPLICATION_AUDIT.md | Duplicate remediation at root vs docs | Consolidate to FINAL only, move others to audit/ |
| Runtime Validation | CURRENT_RUNTIME_VALIDATION.md, RECORDED_RUNTIME_VALIDATION.md, HTTP_CLIENT_RUNTIME_FIX.md, VIDEO_ASSET_RUNTIME_QUERY_TRACE.md | Overlapping validation traces | Keep CURRENT, archive rest |
| Dashboard | DASHBOARD_ARCHITECTURE.md, DASHBOARD_WORKFLOW.md, RECORDED_DASHBOARD_WORKFLOW.md | Recorded workflow subset of main | Merge or keep main |
| Benchmark | BENCHMARK_REPORT.md, BENCHMARK_REPRODUCTION.md, MODEL_BASELINE.md, LOW_RESOURCE_PROFILE.md | 4 files; report + reproduction intentional but baseline duplicate | Keep REPORT + REPRODUCTION, verify BASELINE diff |
| Complete Bundle | COMPLETE_PROJECT_DOCUMENTATION.md vs root .docx (75KB) | .md generated from .docx via scripts/generate_complete_documentation.py | Keep one source (docx is thesis deliverable), .md is derivable |

## 2. Obsolete / Contradictory
- PHASE_2_IMPLEMENTATION.md, PHASE_4_TEST_REPORT.md, PHASE_6_TEST_REPORT.md — phase reports superseded by FINAL_QA_REPORT.md + FINAL_PROJECT_STATUS.md.
- OLD_FAILURE_PATH_AUDIT.md — marked old, not referenced by code.
- CROSS_SERVICE_VIDEO_TRANSFER_ROOT_CAUSE.md — root cause fixed, retained for thesis but not runtime.

## 3. Generated / Temporary Reports (Root Level)
Root *.md that duplicate docs/ content:
- DATABASE_PERSISTENCE_AUDIT.md (also docs/DATABASE_IMPLEMENTATION.md)
- EVENT_COMPLIANCE_MATRIX.md (overlaps EVENT_TAXONOMY_V2)
- EVIDENCE_AUDIT.md, PHONE_DETECTION_AUDIT.md, TRACKING_FAILURE_REPORT.md (all temporal audits, not referenced)
These are **safe to archive** to docs/audit/ or delete after thesis submission.

## 4. Research Docs — NO DUPLICATES
- DATASET_CARD.md, DATASET_VERSIONING.md, DATASET_SPLIT_POLICY.md, DATA_COLLECTION_PROTOCOL.md, DATA_RETENTION_POLICY.md, QUALITY_CONTROL.md, ANNOTATION_GUIDE.md, CONSENT_TEMPLATE.md — each unique, required for reproducibility & ethics. **Do Not Delete**.

## 5. Singletons to Keep (Thesis Deliverables)
ARCHITECTURE.md, API_CONTRACT.md, DATABASE_DESIGN.md, SYSTEM_REQUIREMENTS.md, TEST_STRATEGY.md, SECURITY_AUDIT.md, etc. (unique).

## 6. Savings Estimate
- Deduplication could remove ~35-45 docs (~300-400 KB markdown, not large but reduces maintenance burden).
- Largest redundancy is VIDEO_ASSET_* (7 files) and DESIGN_SYSTEM_* (3 files).

## 7. Safe Actions
- SAFE TO ARCHIVE (move to docs/audit/): DESIGN_SYSTEM.md, DESIGN_SYSTEM_V2.md, EVENT_TAXONOMY.md, VIDEO_ASSET_*_FIX/TRACE (4), PHASE_* (3), OLD_FAILURE_PATH_AUDIT.md.
- DO NOT DELETE: Any research/ doc, COMPLETE_PROJECT_DOCUMENTATION.docx (thesis), CHANGELOG.md, LICENSE.
