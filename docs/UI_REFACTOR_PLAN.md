# UI Refactor Plan — Phase 5.5

## Objective
Transform Laravel dashboard from basic Bootstrap CRUD (top navbar) into modern surveillance platform UI without changing backend, AI service, or APIs. Keep all 65 tests passing.

## Inspiration
- **ERPNext**: left sidebar, grouped modules, clean cards, muted palette
- **Linear**: dark sidebar, subtle borders, fast navigation, command palette feel
- **Notion**: airy spacing, typography hierarchy, soft shadows
- **SOC dashboards**: KPI strip, live status, amber/red for suspicious, gray for insufficient
- **AI monitoring**: Chart.js metrics, DataTables, evidence timeline

## Current State
- `layouts/bootstrap.blade.php` top navbar dark, single-tier nav, no grouping, no icons, basic spacing
- Views: simple tables, no KPI cards, no surveillance cues

## Target Layout
- **Fixed left sidebar** (260px, collapsible offcanvas <992px): logo + search, grouped sections, icons (bootstrap-icons), active state with left border accent, badge counts, user footer
- **Top bar** (within main): breadcrumb, AI notice (amber left border), actions
- **Main**: container-fluid with 24px gutters, cards with 0.5rem radius, subtle shadow, header + body
- **Groups**:
  - **Overview**: Dashboard
  - **Monitoring**: Rooms, Sessions, Cameras, Videos, Jobs
  - **Detection**: Events, Evidence, Reviews
  - **System**: Models, Metrics, Audit Logs, Users, Settings, Help

## Pages to Redesign (6)
1. **Dashboard**: KPI cards (Rooms/Sessions/Jobs/Events with icons, trend, color), surveillance strip (live jobs table with progress), recent events timeline, system health card
2. **Events**: filter bar (type/review), table with status badges (text+color), DataTables-like pagination, row emphasis for B1-B4
3. **Evidence**: gallery grid + list toggle, protected badge, incident timeline
4. **Reviews**: event detail 4-section grid (Machine Observation / Supporting Evidence / Human Decision / Audit History) already exists — polish spacing, typography, badges
5. **Models**: table with checksum monospace, license badge, version timeline
6. **Metrics**: Chart.js bar + line, KPI cards (FPS, latency, CPU), table

## Design System Changes (V2)
- Keep tokens but refine: primary #0d6efd, success #16a34a, warning #f59e0b, danger #dc2626, gray 6c757d, bg #f8fafc, sidebar #0f172a
- Typography: Inter/figtree, 14px base, 24px/18px headings, tabular numbers for metrics
- Spacing: 8px base, cards p-4, g-4 grid
- Shadows: `0 1px 3px rgba(0,0,0,0.08)`
- Badges: pill, text+color, icons

## Implementation Steps
1. Overwrite `layouts/bootstrap.blade.php` with fixed sidebar (no new route, no controller change)
2. Update 6 views to use new cards, badges, spacing, icons (keep same route names, keep required strings like "Machine Observation" for tests)
3. Keep `layouts/app`/`guest` for Breeze auth (Tailwind) untouched
4. Verify `npm run build` still passes (Vite builds Tailwind, Bootstrap via CDN)
5. Run `php artisan test` (65 tests) and `vendor/bin/pint --test`

## Non-Goals
- No backend logic, no AI service, no API, no migration, no config change
- No new dependencies (Bootstrap Icons already via CDN)

## Acceptance
- Sidebar fixed left, grouped, icons, responsive (offcanvas), KPI cards visible, 6 pages redesigned, tests pass
