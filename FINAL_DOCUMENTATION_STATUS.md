# Final Documentation Status
Date: 2026-09-07 | Post-consolidation

## 1. README Audit

| README | Path | Type | Verdict |
|---|---|---|---|
| Project README | /README.md | Project-specific (thesis, architecture, taxonomy, install) | ACTIVE — KEEP |
| Dashboard README | dashboard/README.md | **Was default Laravel boilerplate (59 lines, Laravel sponsors etc.)** — **REPLACED 2026-09-07** with project-specific dashboard README (purpose, roles, setup, DB/queue/AI integration, taxonomy, evidence, camera, security) | ACTIVE — KEEP (now project-specific) |
| AI Service README | ai-service/README.md | Minimal project-specific (FastAPI YOLO, endpoints, AGPL) | ACTIVE — KEEP (adequate, 18 lines) |

Findings: 1 default/boilerplate found and fixed (dashboard). No placeholder/obsolete setup instructions beyond that. Duplicated project descriptions now consolidated via docs/AUDIT_REPORT.md reference.

## 2. docs/ Status (79 active + 33 archived)

### ACTIVE (79) — Keep
| File | Purpose | Referenced? | Thesis? | Runtime? | Maintenance? |
|---|---|---|---|---|---|
| ARCHITECTURE.md | System architecture & config flow | Yes (README, code comments) | YES | YES | YES |
| API_CONTRACT.md | Dashboard ↔ AI Service contract | Yes (AiServiceClient) | YES | YES | YES |
| DATABASE_DESIGN.md | ERD, tables, relations | Yes (migrations) | YES | YES | YES |
| DATABASE_IMPLEMENTATION.md (+merged persistence audit) | Migrations/models/seeders | Yes | YES | YES | YES |
| EVENT_TAXONOMY_V2.md (+merged compliance + V1 note) | 11-event taxonomy canonical | Yes (taxonomy.py) | YES | YES | YES |
| EVENT_TAXONOMY.md | MVP 6-event history | Yes (archived note) | YES (history) | No | YES |
| EVENT_SYSTEM_V2.md / EVENT_ENGINE_V2.md / EVENT_CONFIGURATION_GUIDE.md / EVENT_MANAGEMENT_GUIDE.md | Event engine & config | Yes | YES | YES | YES |
| EVIDENCE_ANNOTATION_SYSTEM.md (+merged evidence audit) | Annotation color/policy | Yes (annotator.py) | YES | YES | YES |
| EVIDENCE_FORMAT.md / EVIDENCE_DOWNLOAD_GUIDE.md | Evidence schema & download | Yes | YES | YES | YES |
| AUTHORIZATION_MATRIX.md | RBAC matrix 5 roles | Yes (RoleMiddleware) | YES | YES | YES |
| SECURITY_AUDIT.md / THREAT_MODEL.md / PRIVACY_REVIEW.md | Security/privacy | Yes | YES | YES | YES |
| INSTALLATION_WINDOWS.md / CAMERA_SETUP.md / RECORDED_VIDEO_MODE.md / LIVE_SURVEILLANCE_MODE.md | Setup & modes | Yes (README) | YES | YES | YES |
| STREAMING_ARCHITECTURE.md / CAMERA_MANAGEMENT_GUIDE.md / EZVIZ_CP1_LITE_COMPATIBILITY.md | Camera & streaming | Yes | YES | YES | YES |
| MODEL_CARD.md / MODEL_BASELINE.md | Model & baseline | Yes | YES | YES | YES |
| BENCHMARK_REPORT.md (+merged phone audit) / BENCHMARK_REPRODUCTION.md / REPRODUCIBILITY.md | Benchmark & repro | Yes | YES | No | YES |
| AUDIT_REPORT.md (new) | Consolidated 6 audits | Yes (archive index) | YES | No | YES |
| TECHNICAL_SUPPLEMENT.md (new) | Orientation/tracking supplement | Yes | YES | No | YES |
| ORIENTATION_METHOD.md / ORIENTATION_METHOD_EVALUATION.md | Head orientation | Yes | YES | YES | YES |
| TRACKING_DESIGN.md (+merged tracking failure) / TRACK_TO_EVENT_MAPPING.md / TEMPORAL_EVENT_RULES.md | Tracking | Yes | YES | YES | YES |
| VIDEO_ASSET_ACTIONS.md / VIDEO_ASSET_CRUD_WORKFLOW.md | Video asset | Yes | YES | YES | YES |
| DASHBOARD_ARCHITECTURE.md / DESIGN_SYSTEM_V3.md / UI_COMPONENTS.md | Dashboard & design canonical | Yes | YES | YES | YES |
| AUTH_UI_V2.md / PROFILE_UI.md / PUBLIC_LANDING_PAGE.md | Auth/UI canonical | Yes | YES | YES | YES |
| FINAL_PROJECT_STATUS.md / FINAL_QA_REPORT.md (+merged accuracy) / CURRENT_STATE_CONSISTENCY_AUDIT.md / PRODUCTION_READINESS_AUDIT.md / CURRENT_RUNTIME_VALIDATION.md | Status/QA | Yes | YES | No | YES |
| KNOWN_LIMITATIONS.md / BEHAVIOR_EVENT_LIMITATIONS.md / DATASET_LIMITATIONS.md | Limitations | Yes | YES | No | YES |
| ANALYSIS_JOB_LIFECYCLE.md / ANALYSIS_JOB_ACTIONS.md / SERVICE_INTEGRATION.md / VIDEO_IO_VALIDATION.md / PERFORMANCE_TUNING.md / LOW_RESOURCE_PROFILE.md | Job/service/perf | Yes | YES | YES | YES |
| AGPL_COMPLIANCE.md / LICENSE_DECISION.md / THIRD_PARTY_NOTICES (root) | Legal | Yes | YES | No | YES |
| COMPLETE_PROJECT_DOCUMENTATION.md | Thesis source md (26 lines) → DOCX | Yes (generate script) | YES | No | YES |
| REPORT_FORMAT.md / RELEASE_CHECKLIST.md / RELEASE_NOTES.md / SYSTEM_REQUIREMENTS.md / PROJECT_SCOPE.md / USE_CASES.md / TEST_STRATEGY.md / USER_ACCEPTANCE_TEST.md / DECISION_LOG.md / DATA_PLAN.md / LOCAL_DEVELOPMENT.md / MASTER_IMPLEMENTATION_PLAN.md / REMEDIATION_REPORT.md / RESEARCH_EVALUATION_REPORT.md | Planning & strategy | Yes | YES | No | YES |
| Others ACTIVE (e.g., DASHBOARD_WORKFLOW.md) | Workflow | Yes | YES | YES | YES |

All ACTIVE needed for thesis ∨ runtime ∨ maintenance → KEEP.

### ARCHIVED (33) — Historical (moved to docs/archive/ per consolidation 2026-09-07)
27 docs + 6 root audits: DESIGN_SYSTEM.md, DESIGN_SYSTEM_V2.md, AUTHENTICATION_UI.md, PROFILE_PAGE_REDESIGN.md, PUBLIC_AUTH_PROFILE_RUNTIME_TRACE.md, PUBLIC_AUTH_PROFILE_VISUAL_QA.md, LANDING_PAGE_DESIGN.md, OLD_FAILURE_PATH_AUDIT.md, FULL_APPLICATION_AUDIT.md, VIDEO_ASSET_ACTIONS_FIX.md, VIDEO_ASSET_FAILURE_ROOT_CAUSE.md, VIDEO_ASSET_PAGE_RUNTIME_FIX.md, VIDEO_ASSET_RUNTIME_QUERY_TRACE.md, VIDEO_ASSET_SOFTDELETE_FIX.md, CROSS_SERVICE_VIDEO_TRANSFER_ROOT_CAUSE.md, HTTP_CLIENT_RUNTIME_FIX.md, ROLE_DEBUG_REPORT.md, RECORDED_RUNTIME_VALIDATION.md, PHASE_2_IMPLEMENTATION.md, PHASE_4_TEST_REPORT.md, PHASE_6_TEST_REPORT.md, DASHBOARD_TEST_REPORT.md, LIVE_MODE_TEST_REPORT.md, RECORDED_MODE_TEST_REPORT.md, RECORDED_DASHBOARD_WORKFLOW.md, UI_REFACTOR_PLAN.md, PROJECT_REMEDIATION_REPORT.md, DATABASE_PERSISTENCE_AUDIT.md, EVENT_COMPLIANCE_MATRIX.md, EVIDENCE_AUDIT.md, PHONE_DETECTION_AUDIT.md, TRACKING_FAILURE_REPORT.md, FINAL_ACCURACY_REPORT.md

Status: DUPLICATE or OBSOLETE (superseded/draft/temporary) but content merged into canonical appendices + indexed in AUDIT_REPORT.md. Not directly referenced by README/thesis/architecture/implementation after merge (only via archive index).

### ARCHIVED duplicates breakdown
- DUPLICATE: DESIGN_SYSTEM (V1/V2 dup V3), AUTHENTICATION_UI dup AUTH_UI_V2, FULL_APPLICATION dup CURRENT_STATE, RECORDED_DASHBOARD dup DASHBOARD_WORKFLOW, 6 root audits dup canonicals (DATABASE_PERSISTENCE etc.)
- OBSOLETE: PHASE_* (historical phase reports), VIDEO_ASSET_*_FIX (temporal fix traces), LANDING_PAGE_DESIGN dup PUBLIC_LANDING, etc.
- OBSOLETE/DUP mix: RECORDED_RUNTIME dup CURRENT_RUNTIME, UI_REFACTOR plan not spec

### Research (8) — ACTIVE, never archived
research/DATASET_CARD.md, DATASET_SPLIT_POLICY.md, DATASET_VERSIONING.md, DATA_COLLECTION_PROTOCOL.md, DATA_RETENTION_POLICY.md, QUALITY_CONTROL.md, ANNOTATION_GUIDE.md, CONSENT_TEMPLATE.md — all ethics, YES for thesis & maintenance.

### Root audit history (20 active root md)
- ACTIVE: README.md, CHANGELOG.md, THIRD_PARTY_NOTICES.md, PROJECT_FINAL_REMEDIATION_REPORT.md, FINAL_DOCUMENTATION_STATUS.md (this), plus 11 previous audit reports (REPOSITORY_INVENTORY.md, DOCUMENTATION_CONSOLIDATION_REPORT.md etc.) — kept as audit trail.
- No root md archived beyond the 6 moved.

## 3. Counts

| Category | Count |
|---|---|
| ACTIVE docs | 79 |
| ARCHIVED docs/archive | 33 |
| ACTIVE root | 20 |
| ACTIVE research | 8 |
| Total project md (active) | 107 (79+20+8) |
| Total archived | 33 |
| Grand total on disk | 140 |

## 4. References Check
All ACTIVE docs referenced by: README (5 direct: INSTALLATION_WINDOWS, CAMERA_SETUP, RECORDED/LIVE modes, EVIDENCE_ANNOTATION) ∨ AUDIT_REPORT/TECHNICAL_SUPPLEMENT (consolidated index) ∨ source code comments (taxonomy, DB design, evidence) ∨ thesis docx. No ACTIVE doc is orphan.
