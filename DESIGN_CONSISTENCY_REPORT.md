# Design Consistency Report — Phase 4 (REAL Blade HTML)
Date: 2026-09-07

## Button Colors/Sizes
- Primary `btn btn-primary` blue #2563EB on dashboard:32 Start Analysis, analysis-jobs:6 New Job, video-assets create — all 38px min-height consistent (guest:23). Secondary outline, danger outline red — single sizes sm/ default, no custom hex per page.

## Badge Colors
- D1 bg-success (dashboard trending + events index:34 `str_starts_with D` D1 success), D2 bg-primary blue :34, D3 bg-warning text-dark, B1-B3 B5 bg-warning text-dark :34, B4 bg-danger :34, S bg-secondary :34 — single mapping across index/show (35). Users roles: system_admin bg-danger, exam_admin warning text-dark, invigilator primary, reviewer success, auditor #7C3AED purple inline :36 — single match per user row + mobile.

## Form/Cards/Typography/Icons/Spacing/Margins/Padding
- Form: input-label 11px uppercase 0.06em, form-control 13px, error input-error — all create/edit identical.
- Cards: .card radius-md shadow-sm border #E2E8F0 — every page (metrics:18 Throughput card, dashboard:11 KPI cards).
- Typography: Inter 13px muted 94A3B8, h4 700 -0.02em, mono for IDs/checksums (evidence/index:42 text-mono) — consistent.
- Icons: bi-* only (bi-building, bi-cpu, bi-activity etc.) — single set, no FontAwesome mix.
- Spacing: gap-3/gap-2, p-3/p-4, mb-4, mt-3 — unified via bootstrap tokens :24 sidebar-w 272 gap.

## Findings
- No mixed Bootstrap patterns (no Tailwind inside bootstrap pages). Only tailwind remains in obsolete layouts/app+navigation (Phase 2) not used by active views. No visual duplication; cards/tables/forms single style.

