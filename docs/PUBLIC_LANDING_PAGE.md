# Public Landing Page

**File:** `dashboard/resources/views/welcome.blade.php`  
**Route:** `GET /` → `view('welcome')` (public, no auth)  
**Design system:** Dashboard bootstrap tokens — Deep navy #0F172A, Elevated #172033, Primary #2563EB, Violet #7C3AED, Teal #0F766E, Success #16A34A, Warning #D97706, Danger #DC2626, BG #F8FAFC, Surface #FFFFFF, Border #E2E8F0, Text #172033, Muted #64748B, Inter + JetBrains Mono, Bootstrap Icons.

## Header

- Logo: shield-lock (project mark, CSS + Bootstrap Icons, not trademarked)
- Project name: AI Classroom + Surveillance Platform
- Nav: Overview (`#overview`), Features (`#features`), How It Works (`#how-it-works`), Responsible AI (`#responsible-ai`)
- Auth CTAs: `Login` always; `Register` conditional `Route::has('register')`; authenticated → `Go to Dashboard` (instead of both)
- Mobile: hamburger collapse, no horizontal overflow.

## Hero

- Eyebrow: `Computer Vision + Behavioral Analysis` + Research Prototype badge.
- **Title:** `AI Classroom Cheating Detection System`
- **Subtitle line 1:** `Real-Time Exam Surveillance Using Computer Vision and Behavioral Analysis`
- **Subtitle line 2:** `AI-assisted recorded and live examination monitoring with computer vision, explainable events, protected evidence, and authorized human review.`
- **Actions:**
  - Authenticated: `Open Dashboard` → `dashboard`
  - Guest: `Log In` → `login`
  - If registration enabled: `Create Account` → `register`
  - `View Documentation` → `help.index`
  - `View Repository` → `https://github.com/Md-Moklesar-Rahman-Bappy/AI-Classroom-Cheating-Detection-System`
- **Status notice:** Pill `Research Prototype — Not production-ready`
- **Visual:** Surveillance mock (max-width 100%, height auto, overflow hidden) — window chrome, gradient `#1e293b→#0f172a`, dashed viewport, bbox overlays (person 0.92, phone 0.87, B1), bottom pill “3 events pending review”, footer stats. No oversized SVG.
- **Trust chips:** Offline + Live, Human review required, Audit trail

## Capability Cards (8)

1. Recorded Video Analysis
2. Live Monitoring
3. Person and Mobile Phone Detection
4. Anonymous Tracking
5. Temporal Event Rules (B1–B4)
6. Evidence and Human Review
7. Metrics and Reports
8. Audit Logging

Each: icon-box 40px, card border #E2E8F0, radius 12px, hover `shadow-md`.

## How It Works

Flow steps (responsive row, arrows horizontal desktop / rotated 90° mobile):

`Video or Camera → AI Processing → Observable Events → Protected Evidence → Human Review → Report`

- Video or Camera: File · Webcam · RTSP
- AI Processing: YOLO + Tracking
- Observable Events: D1/D2/B1–B4
- Protected Evidence: Snapshot/Clip
- Human Review: highlighted `#EFF6FF`, primary border
- Report: Export + Audit

Caption: `FastAPI AI service (8001) ↔ Laravel Dashboard — MySQL · RBAC · Encrypted storage · Rate limiting`

## Responsible AI

- Heading: `AI flags, humans decide`
- Quote (exact):

> “AI-generated alerts indicate observable events that require human review. An alert is not proof of academic misconduct. Final academic or disciplinary decisions remain with authorized human reviewers and the institution.”

- Card with amber left border `#fffbeb`.
- Bullets: No facial recognition, no emotion inference, no protected-characteristic inference, no auto-disciplinary action, role-based evidence access.

## Implementation Status (4 cards)

- Recorded mode: **Verified** (upload/processing/output verified)
- Webcam / test live mode: **Verified** (per repository evidence)
- Direct EZVIZ RTSP/ONVIF: **Unverified** (unless source proves)
- Real-participant evaluation: **Pending / Blocked** (data-governance/consent)

## Research Contributions (5)

01 AI-assisted framework, 02 Lightweight deployment (YOLO11n), 03 Human-review workflow, 04 Event taxonomy, 05 Responsible AI stance.

## Footer

- Project title, researcher Md Moklesar Rahman, institution Jahangirnagar University, supervisor Risala Tasin Khan, PhD
- Links: Login, Register (if enabled), Documentation, Repository
- Responsible-use paragraph, version `config('app.version')`, © 2026
- No email/phone unless already public (only researcher email if already in README is *not* shown on landing per spec — footer shows no email per spec’s “Do not expose email unless already intentionally public” — here omitted).

## Responsive

- Tested viewports: 360×800, 375×812, 390×844, 412×915, 768×1024, 1024×768, 1366×768, 1440×900, 1920×1080
- No `width:100vw`, no oversized SVG, `max-width:100%`, `height:auto`, `overflow:hidden` on hero media.
- Hero `clamp(28px,5vw,42px)`, buttons `flex-wrap`, cards `col-md-6 col-lg-3`, flow arrows rotate on mobile, footer `col-md-5/3/4` stacks.

## Accessibility

- Skip link, one H1, heading order, `nav` aria-label, `role=note` AI notice, `role=img` mock with aria-label, focus ring visible, 4.5:1 contrast, `prefers-reduced-motion` respected.

## Build

- CDN Bootstrap 5.3 + Icons, no `@vite` on this page, so no Tailwind conflict. `npm run build` still validates for app assets.
