# MARKDOWN INVENTORY — AI Classroom Cheating Detection System
Date: 2026-09-07 | Scan: `dir /s *.md` excluding vendor/node_modules

## Summary
| Scope | Count | Notes |
|---|---|---|
| docs/*.md | 103 | Primary documentation |
| research/*.md | 8 | Ethics / dataset / protocol |
| Root *.md | 23 | README, reports, audits, changelog |
| ai-service/README.md + dashboard/README.md | 2 | Service readmes |
| **Project-relevant total** | **136** | Vendor docs excluded ( ~600 md in vendor/node_modules ignored) |

## Methodology for 7 Checks
Per file: grep `README` mentions, grep `docs/` cross-links, grep source comments (`-r "FILENAME"`), check thesis.docx manifest, check `scripts/generate_complete_documentation.py`, manual usefulness review.

## A. Root Level (23)
| Path | Purpose | README? | docs? | Source Code? | Thesis? | Generated? | Still Useful? |
|---|---|---|---|---|---|---|---|
| README.md | Project entry + citation | — | Yes (central) | No | YES (defense) | No | YES |
| CHANGELOG.md | Version history | No | No | No | No | No | YES |
| LICENSE | — | No | No | No | No | No | — (not md) |
| THIRD_PARTY_NOTICES.md | Attribution | No | LICENSE_DECISION | No | Thesis appendix | No | YES |
| DATABASE_PERSISTENCE_AUDIT.md | DB audit (root copy) | No | DATABASE_IMPLEMENTATION | No | No | No | PARTIAL (dup) |
| EVENT_COMPLIANCE_MATRIX.md | Event taxonomy compliance | No | EVENT_TAXONOMY_V2 | No | Thesis | No | PARTIAL (dup) |
| EVIDENCE_AUDIT.md | Evidence system audit | No | EVIDENCE_ANNOTATION_SYSTEM | No | Thesis | No | PARTIAL |
| FINAL_ACCURACY_REPORT.md | Accuracy metrics | No | FINAL_QA_REPORT | No | Thesis | No | PARTIAL |
| PHONE_DETECTION_AUDIT.md | Phone detection audit | No | PHONE_DETECTION (via BENCHMARK) | No | Thesis | No | PARTIAL |
| TRACKING_FAILURE_REPORT.md | Tracker failure analysis | No | TRACKING_DESIGN | No | Thesis | No | PARTIAL |
| PROJECT_REMEDIATION_REPORT.md | Early remediation | No | PROJECT_FINAL_REMEDIATION_REPORT | No | No | No | NO (superseded) |
| PROJECT_FINAL_REMEDIATION_REPORT.md | Final remediation | No | Yes (docs/REMEDIATION_REPORT) | No | Thesis | No | YES |
| REPOSITORY_INVENTORY.md | Phase1 audit (generated 2026-09-07) | No | No | No | No | Yes (this audit) | YES (audit) |
| SAFE_REMOVAL_PLAN.md | Safe removal | No | No | No | No | Yes | YES (audit) |
| SAFE_REMOVAL_MATRIX.md | Matrix | No | No | No | No | Yes | YES |
| STORAGE_USAGE_REPORT.md | Storage | No | No | No | No | Yes | YES |
| ARTIFACT_AUDIT_REPORT.md | Artifacts | No | No | No | No | Yes | YES |
| DOCUMENTATION_CLEANUP_REPORT.md | Docs cleanup | No | No | No | No | Yes | YES |
| UNUSED_FILES_REPORT.md | Unused files | No | No | No | No | Yes | YES |
| DEAD_CODE_REPORT.md | Dead code | No | No | No | No | Yes | YES |
| DRY_RUN_CLEANUP.md | Dry run | No | No | No | No | Yes | YES |
| TEST_CLEANUP_REPORT.md | Test audit | No | No | No | No | Yes | YES |
| CLEANUP_EXECUTION_REPORT.md | Cleanup exec | No | No | No | No | Yes | YES |
| POST_CLEANUP_VALIDATION.md | Validation | No | No | No | No | Yes | YES |

## B. Docs Level (103) — Grouped
### Thesis-Critical / Protected (MUST KEEP — 34 files)
ARCHITECTURE.md, API_CONTRACT.md, DATABASE_DESIGN.md, DATABASE_IMPLEMENTATION.md, EVENT_TAXONOMY.md, EVENT_TAXONOMY_V2.md, EVIDENCE_FORMAT.md, EVIDENCE_ANNOTATION_SYSTEM.md, EVIDENCE_DOWNLOAD_GUIDE.md, AUTHORIZATION_MATRIX.md, SECURITY_AUDIT.md, THREAT_MODEL.md, PRIVACY_REVIEW.md, AGPL_COMPLIANCE.md, LICENSE_DECISION.md, INSTALLATION_WINDOWS.md, CAMERA_SETUP.md, RECORDED_VIDEO_MODE.md, LIVE_SURVEILLANCE_MODE.md, STREAMING_ARCHITECTURE.md, MODEL_CARD.md, MODEL_BASELINE.md, BENCHMARK_REPORT.md, BENCHMARK_REPRODUCTION.md, REPRODUCIBILITY.md, DATASET_LIMITATIONS.md, RESEARCH_EVALUATION_REPORT.md, DATA_PLAN.md, SYSTEM_REQUIREMENTS.md, PROJECT_SCOPE.md, USE_CASES.md, TEST_STRATEGY.md, USER_ACCEPTANCE_TEST.md

All: README? No (except 5), docs? Yes, Source? Yes (referenced in code comments/config), Thesis? YES, Generated? No, Useful? YES

### Research Protocol (8 research/*.md — PROTECTED)
research/DATASET_CARD.md, DATASET_SPLIT_POLICY.md, DATASET_VERSIONING.md, DATA_COLLECTION_PROTOCOL.md, DATA_RETENTION_POLICY.md, QUALITY_CONTROL.md, ANNOTATION_GUIDE.md, CONSENT_TEMPLATE.md — all thesis ethics, no README ref, still YES.

### Deployment & Runtime (8)
LOCAL_DEVELOPMENT.md, CURRENT_RUNTIME_VALIDATION.md, RECORDED_RUNTIME_VALIDATION.md, PRODUCTION_READINESS_AUDIT.md, CURRENT_STATE_CONSISTENCY_AUDIT.md, FULL_APPLICATION_AUDIT.md, FINAL_PROJECT_STATUS.md, FINAL_QA_REPORT.md — Useful YES, but FULL_APPLICATION_AUDIT duplicates CURRENT_* (see duplicate report).

### Event System Cluster (9)
EVENT_SYSTEM_V2.md, EVENT_ENGINE_V2.md, EVENT_CONFIGURATION_GUIDE.md, EVENT_MANAGEMENT_GUIDE.md, EVENT_SYSTEM_REMEDIATION_REPORT.md, TRACK_TO_EVENT_MAPPING.md, TEMPORAL_EVENT_RULES.md, BEHAVIOR_EVENT_LIMITATIONS.md, DECISION_LOG.md — Core, keep.

### Video Asset Cluster (7 — 4 are fix traces)
VIDEO_ASSET_ACTIONS.md, VIDEO_ASSET_CRUD_WORKFLOW.md (KEEP), VIDEO_ASSET_ACTIONS_FIX.md, VIDEO_ASSET_FAILURE_ROOT_CAUSE.md, VIDEO_ASSET_PAGE_RUNTIME_FIX.md, VIDEO_ASSET_RUNTIME_QUERY_TRACE.md, VIDEO_ASSET_SOFTDELETE_FIX.md (FIX traces → ARCHIVE candidates).

### Design System Cluster (6)
DESIGN_SYSTEM.md, DESIGN_SYSTEM_V2.md, DESIGN_SYSTEM_V3.md, UI_COMPONENTS.md, DASHBOARD_ARCHITECTURE.md, DESIGN_SYSTEM_V3 is canonical — V1/V2 superseded.

### Auth/Profile/UI Cluster (12)
AUTHENTICATION_UI.md, AUTH_UI_V2.md, PROFILE_UI.md, PROFILE_PAGE_REDESIGN.md, PUBLIC_AUTH_PROFILE_RUNTIME_TRACE.md, PUBLIC_AUTH_PROFILE_VISUAL_QA.md, PUBLIC_LANDING_PAGE.md, LANDING_PAGE_DESIGN.md, REGISTRATION_ACCESS_DECISION.md, PRIVILEGED_ACCOUNT_DELETION_POLICY.md, UI_REFACTOR_PLAN.md, DASHBOARD_WORKFLOW.md + RECORDED_DASHBOARD_WORKFLOW.md — many overlaps.

### Test/Validation Reports (6)
PHASE_2_IMPLEMENTATION.md, PHASE_4_TEST_REPORT.md, PHASE_6_TEST_REPORT.md, DASHBOARD_TEST_REPORT.md, LIVE_MODE_TEST_REPORT.md, RECORDED_MODE_TEST_REPORT.md — historical, superseded by FINAL_QA_REPORT.

### Detector/Tracker/Benchmark Internals (10)
TRACKING_DESIGN.md, ORIENTATION_METHOD.md, ORIENTATION_METHOD_EVALUATION.md, VIDEO_IO_VALIDATION.md, PERFORMANCE_TUNING.md, LOW_RESOURCE_PROFILE.md, SERVICE_INTEGRATION.md, CAMERA_MANAGEMENT_GUIDE.md, ANALYSIS_JOB_LIFECYCLE.md, ANALYSIS_JOB_ACTIONS.md — Keep (core), some overlap.

### Singletons (misc)
COMPLETE_PROJECT_DOCUMENTATION.md (generated, keep as thesis source), REPORT_FORMAT.md, RELEASE_CHECKLIST.md, RELEASE_NOTES.md, KNOWN_LIMITATIONS.md, MASTER_IMPLEMENTATION_PLAN.md, HTTP_CLIENT_RUNTIME_FIX.md, CROSS_SERVICE_VIDEO_TRANSFER_ROOT_CAUSE.md, ROLE_DEBUG_REPORT.md, VIDEO_ASSET_ACTIONS_FIX (already), OLD_FAILURE_PATH_AUDIT.md, etc.

### C. Service Readmes (2)
ai-service/README.md, dashboard/README.md — both KEEP (entry points), referenced by docs/LOCAL_DEVELOPMENT.

## Still Useful? Summary
- YES: ~85 files (thesis + runtime + research)
- PARTIAL/DUP: ~40 files (audit copies, versioned design/taxonomy, fix traces, phase reports)
- NO: 1 file PROJECT_REMEDIATION_REPORT.md superseded by FINAL.

Evidence: `grep -r "EVENT_TAXONOMY_V2" docs/ app/` shows 12 refs; `grep -r "DESIGN_SYSTEM_V3"` 4 refs; `grep -r "VIDEO_ASSET_*_FIX"` 0 refs beyond own file.
