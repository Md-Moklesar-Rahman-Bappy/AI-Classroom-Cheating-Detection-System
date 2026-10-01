# Design System V2 — Surveillance Platform

## Principles (from Linear/Notion/SOC)
- **Clarity over decoration**: muted palette, high contrast for status, generous whitespace
- **Text + color**: every badge/icon has label, never color alone
- **Speed**: sidebar fixed, no page reload jank, focus states visible

## Tokens
- **Sidebar**: bg #0f172a, text #e2e8f0, muted #94a3b8, accent #0d6efd (left border 3px for active), width 260px, collapsed <992px offcanvas
- **Main**: bg #f8fafc, card bg #ffffff, border #e2e8f0, shadow `0 1px 3px rgba(15,23,42,0.08)`, radius 12px
- **Colors**: primary #0d6efd, success #16a34a, warning #f59e0b (amber-500), danger #dc2626, info #06b6d4, gray #64748b
- **Status**: Normal bg-success, Pending bg-warning text-dark, Suspicious bg-danger, Insufficient bg-secondary, Active bg-primary

## Typography
- **Family**: Inter/figtree (Bunny), fallback system sans, 14px base, 1.5 line-height
- **Scale**: h2 24px 600, h5 16px 600, body 14px, small 12px, tabular numbers for metrics (`font-variant-numeric: tabular-nums`)
- **Headings**: tracking -0.01em, color #0f172a

## Spacing
- Base 8px, `p-4` cards, `g-4` grids, `py-4` main, `mb-4` sections, `gap-3` flex

## Borders & Shadows
- Border 1px solid #e2e8f0, radius 12px, shadow as above, hover shadow `0 4px 12px rgba(0,0,0,0.08)`

## Components
- **Sidebar**: fixed, height 100vh, scroll auto, logo 20px, search input (sm, bg #1e293b), section label 11px uppercase tracking 0.08em, nav-link 14px py-2 px-3 rounded 8px, icon 16px, active bg #1e293b + left accent, badge 11px
- **Top bar**: breadcrumb 13px, AI notice icon amber, user dropdown
- **KPI Card**: icon circle 36px (bg tint), value 28px 600, label 12px uppercase, trend 12px, progress thin 4px
- **Table**: thead 12px uppercase muted, row 14px, hover bg #f8fafc, pagination small
- **Badge**: pill, 11px, text+color, icon 12px
- **Button**: 14px, radius 8px, focus ring 0 0 0 3px rgba(13,110,253,0.15)
- **Form**: control 14px, focus border primary, error text 12px red
- **Alert**: left border 4px, AI notice amber, success green
- **Empty**: card dashed border, icon 32px muted, text 14px

## Responsive
- Sidebar: desktop fixed, tablet overlay offcanvas (translate -100% → 0), toggle button (navbar-toggler) visible <992px, backdrop
- Main: margin-left 260px desktop, 0 mobile, content max-width 1400px centered
- Grid: 4-col KPI → 2-col tablet → 1-col mobile

## Icons
- bootstrap-icons 1.11.3 via CDN: speedometer2, shield-lock, camera-video, collection-play, cpu, activity, file-earmark-bar-graph, people, gear, question-circle, etc., 16px, muted until hover/active

## Accessibility
- Focus visible (ring), badge text+color, table headers, nav landmark, skip link, contrast AA

## No Dark Mode
- Not implemented to avoid delay (spec: only if not delaying core work)
