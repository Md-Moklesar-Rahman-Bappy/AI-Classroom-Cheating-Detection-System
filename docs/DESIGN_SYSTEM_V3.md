# Design System V3 — AI Classroom Surveillance Platform

**Version:** 3.0  
**Date:** 2026-08-31  
**Framework:** Laravel Blade + Bootstrap 5.3 + Bootstrap Icons + Vite  
**Status:** Implemented (bootstrap.blade.php)

## 1. Principles

- Clean enterprise SaaS + SOC dashboard influence
- Restrained color via purposeful accents
- Text + color for every status (never color alone)
- High information density with clarity
- Accessible, keyboard-navigable, responsive

## 2. Tokens

```css
:root {
  --color-primary: #2563EB; --color-primary-dark: #1D4ED8; --color-primary-soft: #DBEAFE;
  --color-secondary: #7C3AED; --color-secondary-soft: #EDE9FE;
  --color-teal: #0F766E; --color-teal-soft: #CCFBF1;
  --color-success: #16A34A; --color-success-soft: #DCFCE7;
  --color-warning: #D97706; --color-warning-soft: #FEF3C7;
  --color-danger: #DC2626; --color-danger-soft: #FEE2E2;
  --color-info: #0284C7; --color-info-soft: #E0F2FE;
  --color-sidebar: #0F172A; --color-sidebar-elevated: #172033;
  --color-sidebar-text: #CBD5E1; --color-sidebar-active: #FFFFFF;
  --color-background: #F5F7FB; --color-surface: #FFFFFF; --color-surface-muted: #F8FAFC;
  --color-border: #E2E8F0; --color-text: #172033; --color-text-muted: #64748B; --color-text-subtle: #94A3B8;
  --radius-sm: 8px; --radius-md: 12px; --radius-lg: 16px;
  --shadow-sm: 0 1px 2px rgba(15,23,42,.06); --shadow-md: 0 4px 12px rgba(15,23,42,.08);
  --sidebar-w: 272px; --sidebar-collapsed: 72px; --topbar-h: 56px;
  --font-sans: 'Inter', system-ui, -apple-system, Segoe UI, sans-serif;
  --font-mono: 'JetBrains Mono', ui-monospace, monospace;
}
```

## 3. Typography

- H1 24px/700, H2 20px/700, H3 16px/600, H4 14px/600 uppercase tracked
- Body 14px/20px, Small 12px/16px, Micro 11px uppercase tracked 0.06em
- Line-height 1.5, tabular-nums for metrics

## 4. Components

**Cards:** `border #E2E8F0`, `radius 12px`, `shadow-sm`, header `bg-white` + `border-bottom`, padding 16–20px
**KPI cards:** icon 36px rounded 8px, value 28px/700 tabular-nums
**Badges:** 11px tracked, `text+icon+color`, always with label
**Buttons:** primary #2563EB, focus ring 3px rgba(37,99,235,.15), min 36px touch
**Inputs:** 38px height, border #E2E8F0, focus primary ring, labels 11px uppercase muted
**Tables:** head 11px uppercase muted, row hover #F8FAFC, responsive wrapper always, mobile card fallback
**Alerts/Notice:** AI notice amber left border, success green, danger red, info blue
**Empty state:** centered card, 48px icon in #F1F5F9, 13px muted description + action
**Loading:** spinner + skeleton shimmer
**Pagination:** centered, 32px min

## 5. Color Semantics

| Domain | Green | Blue | Amber | Red |
|---|---|---|---|---|
| Detection | D1 Person detected | D2 Mobile Phone Detected | B* needs review | B* suspicious |
| Review | Dismissed as Normal | — | Pending / Needs Further Review | Confirmed Suspicious |
| Job | Completed | Processing/Queued | Pending | Failed |
| Health | Online | — | Degraded | Offline/Failed |
| Renderer | person (0) bbox | phone (67) bbox | — | — |

All statuses use text + color + icon.

## 6. Layout

- Sidebar fixed 272px, collapsible to 72px desktop, offcanvas <992px with backdrop, Escape+focus trap, active left 3px primary border
- Topbar 56px, breadcrumb, health badge, mobile hamburger
- Content max 1400px centered, 24px padding (16px mobile)
- Footer 12px muted, 16px padding

## 7. Breakpoints

360, 375, 390, 412, 768, 820, 1024, 1366, 1440, 1920 — tested via responsive spec

## 8. Accessibility

- Skip link, one H1/page, heading order, labels + required indicators, focus ring visible, 4.5:1 contrast, keyboard nav, modal focus trap, icon aria-hidden + sr text, alt text, reduced-motion
- Target WCAG 2.1 AA (not claimed as certified)

## 9. Iconography

Bootstrap Icons 1.11.3 exclusively; no emoji as primary icon.

## 10. Charts

Chart.js where present; text alternative + table fallback; resize observer; muted grid.

## 11. File

Tokens and styles defined in `dashboard/resources/views/layouts/bootstrap.blade.php` `<style>` and `dashboard/resources/css/app.css` (Vite).
