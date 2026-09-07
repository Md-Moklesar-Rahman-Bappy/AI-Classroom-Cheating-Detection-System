# Dashboard Structure Report — Phase 1
Date: 2026-09-07

## Routes (106 lines web.php + auth.php)
| Route | Controller@Method | View | Policy/Middleware | Role Access |
|---|---|---|---|---|
| GET / | Closure | welcome | — | public |
| GET /health/ai | Closure (AiServiceClient) | json | — | public |
| GET /dashboard | DashboardController@index | dashboard | auth verified | all |
| exam-rooms resource + restore | ExamRoomController 7 actions | exam-rooms/* | auth verified + RoleMiddleware system_admin/exam_admin for create/update/destroy | system_admin, exam_admin |
| exam-sessions resource + restore | ExamSessionController | exam-sessions/* | auth verified | system_admin, exam_admin |
| camera-sources resource + restore | CameraSourceController | camera-sources/* | auth verified | system_admin, exam_admin, invigilator view |
| video-assets only index/create/store/show/edit/update/destroy + restore | VideoAssetController | video-assets/* | auth verified + VideoAssetPolicy | system_admin, exam_admin |
| analysis-jobs only + sync/cancel/retry + report show/download | AnalysisJobController + ReportController | analysis-jobs/*, reports/* | auth verified + AnalysisJobPolicy | system_admin, exam_admin, reviewer |
| detection-events only index/show/destroy + bulkDelete/bulkRestore/restore + review store | DetectionEventController + ReviewDecisionController | detection-events/* | auth verified + role | all (destroy gated system_admin/exam_admin) |
| evidence index/show/download/destroy/bulkDelete/bulkRestore/restore | EvidenceController | evidence/* | role:system_admin,exam_admin,reviewer,invigilator,auditor (destroy only admin) | as listed |
| model-versions resource | ModelVersionController | model-versions/* | auth verified | system_admin |
| audit-logs index | AuditLogController@index | audit-logs/index | role system_admin/auditor/exam_admin | as listed |
| users resource + restore | UserController | users/* | role system_admin | system_admin only |
| trash | Closure (7 onlyTrashed) | trash/index | role system_admin/exam_admin | as listed |
| live index/start/show/stop/health/events/preview | LiveController 7 methods | live/* | role invigilator+ for start/stop, all for view | see web.php |
| settings/help/metrics | Closures | settings/index, help/index, metrics/index (ProcessingMetric) | auth verified | all |
| profile edit/update/destroy | ProfileController | profile/edit | auth | self |
| auth 8 routes | Auth/* (Registered, AuthenticatedSession etc.) | auth/* | guest/auth | — |

## Controllers (16 in app/Http/Controllers)
Dashboard, ExamRoom, ExamSession, CameraSource, VideoAsset, AnalysisJob, DetectionEvent, Evidence, ReviewDecision, AuditLog, User, ModelVersion, Live, Profile, Report + Auth 6. All referenced by routes (0 dead).

## Models (14)
User (roles/permissions), Role, Permission, ExamRoom, ExamSession, CameraSource, VideoAsset, AnalysisJob, DetectionEvent, EventEvidence, ReviewDecision, ProcessingMetric, ModelVersion, AuditLog — all with fillable/relations.

## Blade Views (81 files)
Layouts: app, bootstrap (canonical 2026-08-31), guest, navigation; auth 6; exam-rooms/sessions 4 each; camera-sources 4; video-assets 4; analysis-jobs 4; detection-events 2; evidence 2; users 3; model-versions 4; live 2; reports 2; audit-logs 1; metrics 1; dashboard 1; profile 3; trash/settings/help 3; errors 8; components 8.

## Layouts/Partials/Components
bootstrap: tokens sidebar #0F172A primary #2563EB Inter+JetBrains Mono, offcanvas <992 backdrop blur, sticky topbar, skip-link, focus-visible. navigation: role-aware links, user dropdown. components: primary/danger/secondary buttons, input-label/text-input/modal etc.

## Middleware/Policies/Requests
RoleMiddleware on 10 route groups, Authenticate/Verified, Policies VideoAssetPolicy/AnalysisJobPolicy, Requests validation in controllers (in:, exists, required).

## Navigation
Sidebar filters by role (auditor hides exam-rooms, invigilator sees live etc.), topbar user menu, trash/help/settings always visible for admin.

## Verdict
No orphan controller/model/view/route; 0 dead policies; navigation consistent with RBAC matrix.
