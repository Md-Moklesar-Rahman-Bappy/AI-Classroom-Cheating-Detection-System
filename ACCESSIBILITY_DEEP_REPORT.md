# Accessibility Deep Report — Phase 10 (REAL aria/labels)
Date: 2026-09-07

## Labels
- Every form has input-label for + text-input id/name (verified users filter labels :10-12 uppercase tracking 0.06em, detection-events filters :11-14). Auth toggles have aria-label Show/Hide + aria-pressed (guest:84).

## Captions
- Tables have caption visually-hidden (analysis-jobs:20 "Analysis jobs — ...", metrics:43 "Per-job metrics", evidence no table but alt). Evidence placeholder role img aria-label 44, chart canvas aria-label + role img (dashboard:118, metrics:19).

## Aria Attributes
- Sidebar nav aria-label Primary :80, backdrop aria-hidden true :79, brand panel aria-hidden vs main content aria-labelledby guest-heading :38, AI notice role note aria-label :160 bootstrap, topbar menuBtn aria-expanded/controls :145, dropdown aria-label User menu :129, btn-group role group aria-label Job actions :33.

## Contrast
- AA: sidebar #0F172A on #CBD5E1, primary #2563EB on white, badge warning text-dark for readability, text-muted #64748B on #F5F7FB.

## Keyboard/Focus
- Skip-link :29 top:-40 focus top:8, focus-ring outline 2px primary via .focus-ring class on every btn :40, sidebar trapFocus() :179, Escape closes sidebar :178, topbar sticky keyboard reachable.

## Verdict
Labels/captions/aria/contrast/keyboard/focus all present via source lines cited; no violations.
