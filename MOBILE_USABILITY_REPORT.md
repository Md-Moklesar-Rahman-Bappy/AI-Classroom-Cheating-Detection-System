# Mobile Usability Report — Phase 8 (REAL d-* classes)
Date: 2026-09-07

## Trace d-none / d-md-none / responsive cards
- dashboard: col-12 col-md-6 col-xl-3 KPI grid — stacks on mobile; topbar Home breadcrumb d-none d-sm-block :147 + d-sm-none title — provides alternative
- detection-events: table-responsive only — no mobile cards but filter row stacks col-12 col-md-3 — table scrolls horizontally, actions btn-group-sm still tappable (no d-none hiding Delete :45)
- evidence: col-12 col-md-6 col-lg-4 grid — stacks to 1 col on mobile, cards p-3, View button 49 visible, no hide
- users: d-none d-md-block table 23 + d-md-none cards 48 — both have Edit link (40 vs 53) — Edit/Delete not hidden
- metrics: d-none d-md-block table 41 + d-md-none cards 54 — both contain FPS/Latency/CPU/Memory — no hidden data
- analysis-jobs: btn-group flex-wrap 320px width — wraps on <375, not hidden
- navigation: sidebar offcanvas d-lg-none menuBtn :145 + backdrop .backdrop blur — mobile nav accessible, close on Escape trapped

## Can Mobile Access Edit/Delete/Restore/View/Download?
- Edit: users 53, events Detail, jobs View/Edit — all btn visible on mobile via cards or groups — Yes
- Delete: events Delete form 46, jobs Delete 39 — forms not d-none — Yes
- Restore: trash view 7 lists with POST restore buttons — not hidden — Yes
- View: evidence View eye 49 — card grid — Yes
- Download: evidence download route + report download — btn visible on show pages — Yes

## Verdict
No mobile hidden actions, no overflow (sidebar translateX, content padding 16px mobile :69), cards provide alternative to tables.
