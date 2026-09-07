# Blade Inventory Report — Phase 1 (REAL source)
Date: 2026-09-07 | Scan: dashboard/resources/views/**/* (81 .blade.php, no docs)

| Path | Purpose | Extends | Includes/Components | Controller | Route | Used |
|---|---|---|---|---|---|---|
| layouts/bootstrap.blade.php:1 | Canonical layout (184 lines) | — | sidebar/topbar/footer/ai-notice skip-link | All authenticated controllers via @extends | all auth routes | Used |
| layouts/guest.blade.php:1 | Auth layout brand+form grid | — | brand-panel surveillance illustration | Auth/* controllers | login/register/reset | Used |
| layouts/app.blade.php:1 | Default Laravel app layout (36 lines) | — | @include layouts.navigation | — | — | **UNUSED** (no view extends it since 2026-08-31 redesign) |
| layouts/navigation.blade.php:1 | Tailwind nav (100 lines) | — | x-application-logo/dropdown | — | — | **UNUSED** (only included by layouts/app, which is unused) |
| dashboard.blade.php:1 | KPI + health + trend chart | layouts.bootstrap:1 | — | DashboardController@index | GET /dashboard | Used |
| exam-rooms/index.blade.php | List + filter | bootstrap | — | ExamRoomController@index | exam-rooms.index | Used |
| exam-rooms/create/edit/show | CRUD forms | bootstrap | components/text-input etc. | ExamRoomController | resource | Used |
| exam-sessions/* (4) | Session CRUD | bootstrap | — | ExamSessionController | resource | Used |
| camera-sources/* (4) | Camera CRUD health badge | bootstrap | — | CameraSourceController | resource | Used |
| video-assets/* (4) | Upload + player | bootstrap | — | VideoAssetController | only index/create/store/show/edit/update/destroy | Used |
| analysis-jobs/* (4) | Job timeline sync/cancel/retry | bootstrap | — | AnalysisJobController | only + sync/cancel/retry/report | Used |
| detection-events/index.blade.php:1 | 11-event filter table | bootstrap | — | DetectionEventController@index | detection-events.index | Used |
| detection-events/show.blade.php | Triptych + review form | bootstrap | — | DetectionEventController@show | detection-events.show | Used |
| evidence/index.blade.php:1 | Evidence gallery (shows Event header) | bootstrap | — | EvidenceController@index | evidence.index | Used but **shows detectionEvent header without listing all evidences globally** (intended per design) |
| evidence/gallery.blade.php | Alternate gallery grid | bootstrap | — | — (not routed) | — | **UNUSED** (evidence route uses index, gallery orphan) |
| users/* (3) | User+role CRUD | bootstrap | — | UserController | resource + restore | Used |
| model-versions/* (4) | Version CRUD | bootstrap | — | ModelVersionController | resource | Used |
| live/index, show (2) | Live monitoring + session | bootstrap | — | LiveController | live.* | Used |
| reports/show, pdf (2) | Report view + PDF | bootstrap | — | ReportController | reports.show/download | Used |
| audit-logs/index | Log table + filter | bootstrap | — | AuditLogController | audit-logs.index | Used |
| metrics/index (70 lines) | KPI + throughput + per-job | bootstrap | Chart.js | Closure (ProcessingMetric) | metrics.index | Used |
| trash/index | 7 soft-deleted lists (8 lines each) | bootstrap | — | Closure | trash.index | Used |
| settings/help/index (2) | Static | bootstrap | — | Closure | settings/help.index | Used |
| auth/* (6) | Login etc. | guest | x-input etc. | Auth/* | auth routes | Used |
| profile/edit + partials (4) | Profile + password | bootstrap | — | ProfileController | profile.* | Used |
| components/* (8) | Buttons/inputs/modal | — | — | — | — | Partially used (primary/secondary/danger reused; dropdown unused after nav deprecation) |
| welcome/errors (9) | Public/error | — | — | — | / , fallback | Used |

**Summary**: 81 files, 2 layouts_unused (app+navigation = Tailwind obsolete since bootstrap became canonical), 1 view unused (evidence/gallery), остальные 78 used.

