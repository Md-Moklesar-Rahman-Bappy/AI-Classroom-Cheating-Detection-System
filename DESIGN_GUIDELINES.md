# Design Guidelines — Phase 14 (Enforced Standard)
Date: 2026-09-07

## Single Systems (No Mixed Styles)
- **Palette**: sidebar #0F172A, primary #2563EB (bg soft #EFF6FF), success #16A34A, warning #D97706, danger #DC2626, gray 100/200/800 — var(--color-*) in bootstrap layout
- **Button**: primary solid blue, secondary outline, danger solid red via components/primary-button etc. — no inline btn-* overrides
- **Card**: .card border shadow-sm p-4, header bg-white border-bottom, kpi-card progress 4px — all pages
- **Table**: table-hover thead bg-light d-none d-md-block + mobile cards d-md-none, caption visually-hidden, empty-state inbox — all index
- **Form**: text-input + input-label + input-error + old() + help text muted 11px — all create/edit
- **Badge**: status-badge D1 green / D2 blue / D3 yellow / B orange / B4 red / S gray / queue warning — event_code mapping single source
- **Spacing**: container-fluid px-4, gap-3/4, p-4, mb-4, header h4/h6 11px uppercase tracking 0.06em — unified
- **Icon**: Bootstrap Icons bi-* + aria-hidden true — single set
- **Typography**: Inter body 13px, heading 700 -0.02em, mono for IDs/metrics tabular-nums — unified

Violations: 0 after 2026-08-31 vite 7.3.6 redesign. Archived V1/V2 design docs moved to docs/archive/.
