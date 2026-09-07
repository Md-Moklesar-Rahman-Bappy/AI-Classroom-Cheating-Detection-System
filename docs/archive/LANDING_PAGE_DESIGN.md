# Landing Page Design — AI Classroom Cheating Detection System

**Version:** 1.0
**Date:** 2026-08-31
**File:** `dashboard/resources/views/welcome.blade.php`
**Route:** `GET /` (public)
**Design system:** Dashboard bootstrap tokens — Sidebar #0F172A, Primary #2563EB, Success #22C55E, Danger #EF4444, Background #F8FAFC, Cards #FFFFFF, Border #E2E8F0, Bootstrap Icons, Inter

## Goals
- Replace default Laravel welcome screen with project-identity landing.
- Hero with title, subtitle and primary CTAs.
- Sections: Features, Architecture Overview, How It Works, Human Review Workflow, Research Contributions, Footer.
- Fully responsive (360, 768, 1024, 1440).

## Tokens & Styling
- `navbar-blur` — white 0.9 + backdrop blur, border #E2E8F0.
- Hero — `background: #0F172A`, text #CBD5E1, heading #fff 800, sub #94A3B8, `btn-primary #2563EB → #1D4ED8`.
- Cards — `border #E2E8F0`, `radius 12px`, `shadow-sm`, white.
- Section label — 11px uppercase tracking 0.08em, muted, 700.
- AI notice — amber left border `#D97706`, bg `#fffbeb`.

## Information Architecture

### Navbar (sticky)
- Brand: shield-lock icon + "AI Classroom" + "Surveillance Platform" + Research Prototype badge.
- Links: Features, Architecture, How It Works, Research + auth buttons (Login/Register or Dashboard if auth).

### Hero
- Left: eyebrow chip (CPU + Research Prototype), H1 "AI Classroom Cheating Detection System", subtitle "Real-Time Exam Surveillance Using Computer Vision and Behavioral Analysis", description, CTA row: Login, Register, Documentation (`https://laravel.com/docs`), GitHub Repository (`https://github.com/Md-Moklesar-Rahman-Bappy/AI-Classroom-Cheating-Detection-System`), trust chips (Offline+Live, Human review, Audit).
- Right: surveillance mock — window chrome (red/yellow/green dots), gradient `#1e293b→#0f172a`, dashed viewport, 4 bbox overlays (person 0.92 green, person 0.88 green, phone 0.87 red, B1 amber), bottom pill "3 events pending review", footer bar with clock 00:14:22 · 24 FPS + badges.

### Features (6 cards)
- Person Detection (YOLO, bbox) — DBEAFE/2563EB.
- Mobile Phone Detection (D2) — FEE2E2/EF4444.
- Tracking & Seat Analysis (ByteTrack) — EDE9FE/7C3AED.
- Head Orientation (B1-B3) — FEF3C7/D97706.
- Recorded & Live Modes — DCFCE7/16A34A.
- Evidence & Audit — CCFBF1/0F766E.
- Hover: `shadow-md` + `translateY(-1px)`.

### Architecture Overview
- Two rows of steps with arrows (desktop) / stacked (mobile).
- Row1: Video Source → Detection (YOLO11n+Pose) → Tracking (ByteTrack) → Behavior (Event Engine) .
- Row2: Evidence → Dashboard (Laravel+Bootstrap) → Human Review (highlighted #EFF6FF) → Audit Log.
- Caption: FastAPI 8001 ↔ Laravel — MySQL · RBAC · Encrypted storage.

### How It Works (2 cards)
- Recorded: Upload → Process (frame→YOLO→tracking→rules) → Annotated video + snapshots → Review timeline → Export report.
- Live: Connect RTSP/webcam → Stream to AI → Alert queue (~200ms) → Review → Retain per policy.

### Human Review Workflow
- Left: vertical timeline (alert → evidence → decision → audit) with dots and badges.
- Right: status semantics table (D1 green, D2 red, B1-B4 amber, Pending amber, Processing blue) + info notice re text+color+icon.

### Research Contributions (5 cards)
- 01 AI-assisted framework (recorded+live).
- 02 Lightweight deployment (YOLO11n, low-resource).
- 03 Human-review workflow with audit.
- 04 Event taxonomy D1/D2/B1-B4.
- 05 Responsible AI (no facial recognition, no auto-accusation).

### Footer (dark #0F172A)
- Left: brand + MSc JU + supervisor + AI notice.
- Center: Links (Login, Register, Documentation, GitHub).
- Right: Contact email + © 2026.

## Responsive
- Bootstrap grid + `clamp()` for hero type.
- Hero columns `col-lg-6` stack on <992px.
- Features `col-md-6 col-lg-4`.
- Architecture `col-6 col-md-2` + hide arrows on mobile.
- Navbar collapses to hamburger <992px.

## Accessibility
- Skip not needed (public), but one H1, heading order, contrast 4.5:1, focus visible.

## Backend
- No controller change. `Route::get('/', fn()=>view('welcome'))` preserved. Auth CTAs use `route('login')`/`route('register')`/`route('dashboard')`.

## Verification
- `GET /` returns 200 unauthenticated; authenticated shows Dashboard button; Lighthouse responsive audit.

