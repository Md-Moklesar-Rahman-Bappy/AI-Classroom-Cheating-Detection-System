# Layout Deep Audit — Phase 2 (REAL source)
Date: 2026-09-07

## Inspected: layouts/bootstrap (184 lines), guest (89), app (36), navigation (100), sidebar/header/footer within bootstrap

## Verification from code
- **Consistent layout**: All authenticated views `@extends("layouts.bootstrap")` (verified 68 occurrences); none extend app after 2026-08-31. Guest auth 6 views extend guest.
- **Navigation**: bootstrap sidebar 81-140 contains 14 nav-links in 5 sections (Overview, Exam Management, Monitoring, Detection & Review, Analytics, Administration) with @can for users, active via request()->routeIs(), offcanvas <991.98 media query :69. navigation.blade.php is obsolete Tailwind (x-data) not included anywhere except app.blade.php which is unused.
- **Spacing**: :root spacing 8/12/16/24, content padding 24px (16px mobile :69), card p-4 — consistent.
- **Card design**: .card border shadow-sm radius-md bg-surface (bootstrap:58) used on every page except welcome/errors.
- **Page title**: @section("title") yielded at topbar breadcrumb :147 and skipped-link, responsive hide d-sm-none span — consistent.
- **Table style**: thead th 11px uppercase tracking 0.06em ( :62) table-hover mb-0; responsive d-none d-md-block + d-md-none cards — consistent.
- **Form style**: guest form-panel + bootstrap text-input heights 38px, form-label uppercase 11px — consistent.
- **Modal**: sweetalert2 + components/modal — single pattern via Swal.fire on delete forms (detection-events/index:60, users etc.).

## Duplicates / Inconsistent / Obsolete
- **Duplicate layouts**: `layouts/app` (Tailwind Vite) vs `layouts/bootstrap` (CDN) — existence of both is duplication. app is **obsolete** since redesign (0 active extends). `layouts/navigation` is orphan included only by app.
- **Consistent**: bootstrap+guest are the only active layouts — no inconsistency between active pages.
- **Obsolete**: app+navigation are safe to archive (not delete immediately per instructions) — they are the classic Laravel starter.

## Verdict
Active layout consistent; 2 obsolete layouts (app, navigation) identified as duplicates from framework scaffolding.

