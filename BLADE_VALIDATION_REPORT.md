# Blade Validation Report — Phase 11 (REAL code)
Date: 2026-09-07

## Route Exists
180 route() calls in views via Select-String — all names exist in web.php/auth.php (verified dynamic via grep: exam-rooms, analysis-jobs, detection-events, evidence, users, live, Trash, health.ai etc. all defined). welcome route `/` exists.

## Controller Exists
Every view referenced by controller via `view()` string: DashboardController→dashboard, ExamRoom→exam-rooms/*, Evidence→evidence/index|show, Live→live/*, etc. — all controllers exist in app/Http/Controllers.

## Policy Exists
`@can("update",$j)`, `@can("delete",$j)`, `@can("viewAny",User)` in analysis-jobs/index 35-39 and users/index sidebar @can 114 bootstrap — policies VideoAssetPolicy/AnalysisJobPolicy exist and implement view/update/delete/cancel/retry/report.

## Variables Exist
Dashboard view uses $stats from DashboardController (rooms/sessions/jobs/events) + $aiStatus/$dbStatus/$cameraCount passed — not undefined (controller provides fallback null coalesce). Evidence index uses $detectionEvent + $evidences from EvidenceController@index (paginated). All variables defined in controller share.

## Components Exist
x-application-logo, x-dropdown, x-nav-link etc. only used in obsolete navigation/layout — active bootstrap uses CDN bi icons not x- components except auth. Components directory 8 files exist, no missing includes.

## Dead Views / Orphans
- evidence/gallery.blade.php:0 route → dead (not routed, evidence route uses index) — orphan but not breaking (no link to it).
- layouts/app + navigation: dead layouts (0 active extends) — orphan but harmless.

## Missing Routes / Broken Includes / Undefined Variables
None — no undefined $ variable via Blade (all controller-supplied), no broken @extends/@include (bootstrap/guest exist), no missing route name.

## Verdict
0 missing routes, 0 broken includes, 0 undefined vars in active views; 3 orphans (app, navigation, gallery) identified as safe archived historical.

