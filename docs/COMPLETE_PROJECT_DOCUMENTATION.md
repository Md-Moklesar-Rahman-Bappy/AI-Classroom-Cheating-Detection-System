# Complete Project Documentation — Markdown Source

This is the intermediate reviewed Markdown source used to generate:
`AI_Classroom_Cheating_Detection_System_Complete_Documentation.docx`

Content verified against repository state (commit feat/ui). All claims trace to verified source files (see docs/audit/COMPLETE_DOCUMENTATION_SOURCE_AUDIT.md). No private paths, secrets, videos, datasets, or model weights included. Real-data evaluation blocked. Synthetic/non-identifiable evaluation only.

For full content see the DOCX file (59.6 KB, 627 paragraphs, 292 headings, all required sections 1-285 plus front matter and appendices).

## UI-FINAL — 2026-08-31 — Production-Grade Redesign

Landing (`/`): Hero (YOLO + ByteTrack badge, surveillance mock, REC, AI Notice), feature cards (8), architecture preview (6-step flow Video→AI→Events→Evidence→Review→Report), Responsible AI + Review workflow cards, implementation status (verified/pending), research contributions, footer with Login/Register/GitHub/Docs. No Laravel welcome. Mobile 360/375/390/768 no overflow, offcanvas sidebar on mobile, fixed on desktop.

Auth (`login`/`register`/`forgot-password`/`reset-password`/`verify-email`/`confirm-password`): guest layout (brand panel + illustration), password visibility toggle (eye/eye-slash, aria-pressed), register confirm toggle + strength indicator (bar + text + req checklist client-only), server truth note, mobile responsive, focus ring, proper labels/aria.

Profile (`/profile`): dashboard shell, avatar (initials), human-readable role badge (description), verification badge, joined date + ID, stats (Created Jobs / Reviews / Audit Activity via models), Security section (email verification, password age, role, audit), password visibility toggles (current/new/confirm + delete modal), delete guard (blocks last system_admin).

Dashboard (`/dashboard`): KPIs, AI/DB/Camera/Queue status (text+color+icon, never color alone), System Health, Quick Actions, Event Trend Chart (Chart.js line, last 7 days grouped by DATE(created_at)), Live Monitoring placeholder, Recent Alerts (latest 5 + link to filtered Events). Data never invented; empty states shown.

Shell (`layouts/bootstrap`): design tokens (sidebar #0F172A, primary #2563EB, etc.), Inter + JetBrains Mono, sidebar offcanvas (transform translate on <992, backdrop blur), collapse toggle desktop, sticky topbar, skip-link, AI Notice, focus-visible outline, responsive breakpoints verified.

Other pages: Video Assets, Analysis Jobs, Events, Evidence, Reviews (show with Machine Observation / Evidence / Human Decision + audit), Reports (show + download), Metrics (KPIs + throughput canvas + per-job table with mobile cards), Audit Logs (filter + table + mobile cards). All use same tokens, accessible labels, pagination.

Validation: `php artisan test` 160 passed, `npm run build` (vite 7.3.6, 59 modules, manifest + css 45kB + js 107kB).

Screenshots: see `docs/screenshots/` (landing, login, register, profile, dashboard with trend chart, video-assets, analysis-jobs, events, evidence, reviews, reports, metrics, audit-logs) at 360x800 375x812 390x844 768x1024.
