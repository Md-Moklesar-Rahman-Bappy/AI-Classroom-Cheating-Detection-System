<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>AI Classroom Cheating Detection System — Real-Time Exam Surveillance Using Computer Vision and Behavioral Analysis</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400&display=swap" rel="stylesheet">
<style>
:root{--sidebar:#0F172A;--sidebar-elevated:#172033;--primary:#2563EB;--primary-dark:#1D4ED8;--secondary:#7C3AED;--teal:#0F766E;--success:#16A34A;--warning:#D97706;--danger:#DC2626;--bg:#F8FAFC;--bg2:#F5F7FB;--surface:#FFFFFF;--border:#E2E8F0;--text:#172033;--muted:#64748B;--subtle:#94A3B8;--radius-md:12px;--radius-lg:16px;--shadow-sm:0 1px 2px rgba(15,23,42,.06);--shadow-md:0 4px 12px rgba(15,23,42,.08)}
*{font-family:Inter,system-ui,sans-serif}body{background:var(--bg);color:var(--text);margin:0}
.skip-link{position:absolute;top:-40px;left:8px;background:var(--primary);color:#fff;padding:8px 12px;border-radius:8px;z-index:9999;text-decoration:none;font-size:13px}
.skip-link:focus{top:8px}
.navbar-blur{backdrop-filter:blur(8px);background:rgba(255,255,255,.92);border-bottom:1px solid var(--border)}
.btn-primary{background:var(--primary);border-color:var(--primary);font-weight:600}
.btn-primary:hover{background:var(--primary-dark);border-color:var(--primary-dark)}
.hero{background:radial-gradient(800px 400px at 20% 0%, rgba(37,99,235,.18), transparent 60%), linear-gradient(180deg, #0F172A 0%, #111c3a 100%);color:#CBD5E1;overflow:hidden;position:relative}
.hero h1{color:#fff;font-weight:800;letter-spacing:-.03em;line-height:1.05}
.hero .lead{color:#CBD5E1}
.card{border:1px solid var(--border);border-radius:var(--radius-md);box-shadow:var(--shadow-sm);background:var(--surface)}
.section-label{font-size:11px;letter-spacing:.08em;text-transform:uppercase;color:var(--muted);font-weight:700}
.icon-box{width:40px;height:40px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:18px;flex-shrink:0}
.feature-card:hover{box-shadow:var(--shadow-md);transform:translateY(-1px);transition:all .15s}
.ai-notice{background:#fffbeb;border-left:4px solid var(--warning);padding:12px 16px;border-radius:8px;font-size:13px;color:#92400e}
.footer-dark{background:var(--sidebar);color:#94A3B8}
.footer-dark a{color:#CBD5E1;text-decoration:none}
.footer-dark a:hover{color:#fff}
.surveillance-mock{border:1px solid rgba(255,255,255,.14);border-radius:12px;overflow:hidden;background:#0B1224;max-width:100%;height:auto;overflow:hidden}
.surveillance-mock *{max-width:100%}
.mock-header{height:32px;background:var(--sidebar-elevated);display:flex;align-items:center;gap:6px;padding:0 10px}
.mock-dot{width:8px;height:8px;border-radius:50%}
.hero-illustration{max-width:100%;height:auto;overflow:hidden}
.flow-step{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius-md);padding:16px;text-align:center;min-height:92px;display:flex;flex-direction:column;align-items:center;justify-content:center}
.flow-arrow{color:var(--muted);display:flex;align-items:center;justify-content:center}
@media(max-width:767.98px){.flow-arrow{transform:rotate(90deg)}}
@media(prefers-reduced-motion:reduce){*{transition:none!important;animation:none!important}}
</style>
</head>
<body>
<a href="#main-content" class="skip-link">Skip to content</a>
<nav class="navbar navbar-expand-lg sticky-top navbar-blur py-2" aria-label="Primary">
<div class="container" style="max-width:1200px">
<a class="navbar-brand d-flex align-items-center gap-2" href="/" style="min-width:0">
<span class="bg-primary rounded d-flex align-items-center justify-content-center flex-shrink-0" style="width:30px;height:30px" aria-hidden="true"><i class="bi bi-shield-lock text-white"></i></span>
<span style="font-weight:700;letter-spacing:-.02em;color:var(--sidebar)">AI Classroom</span><span class="d-none d-sm-inline" style="font-size:11px;letter-spacing:.08em;text-transform:uppercase;color:var(--muted);margin-left:4px">Surveillance Platform</span>
</a>
<button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMain" aria-controls="navMain" aria-expanded="false" aria-label="Toggle navigation"><span class="navbar-toggler-icon"></span></button>
<div class="collapse navbar-collapse" id="navMain">
<ul class="navbar-nav ms-auto align-items-lg-center gap-lg-2">
<li class="nav-item"><a class="nav-link" href="#overview">Overview</a></li>
<li class="nav-item"><a class="nav-link" href="#features">Features</a></li>
<li class="nav-item"><a class="nav-link" href="#how-it-works">How It Works</a></li>
<li class="nav-item"><a class="nav-link" href="#responsible-ai">Responsible AI</a></li>
@auth
<a href="{{ route('dashboard') }}" class="btn btn-primary btn-sm ms-lg-2"><i class="bi bi-speedometer2 me-1" aria-hidden="true"></i> Go to Dashboard</a>
@else
<a href="{{ route('login') }}" class="btn btn-outline-primary btn-sm ms-lg-2">Login</a>
@if(Route::has('register'))
<a href="{{ route('register') }}" class="btn btn-primary btn-sm">Register</a>
@endif
@endauth
</ul>
</div>
</div>
</nav>
<main id="main-content" tabindex="-1">
<section class="hero py-5" id="overview">
<div class="container" style="max-width:1200px">
<div class="row align-items-center g-4">
<div class="col-lg-6">
<div class="d-inline-flex align-items-center gap-2 mb-3" style="background:rgba(37,99,235,.16);border:1px solid rgba(37,99,235,.3);border-radius:999px;padding:4px 12px;font-size:11px;letter-spacing:.06em;text-transform:uppercase;color:#93C5FD"><i class="bi bi-cpu" aria-hidden="true"></i> Computer Vision + Behavioral Analysis <span class="badge bg-success" style="font-size:10px">Research Prototype</span></div>
<h1 class="display-5 mb-3" style="font-size:clamp(28px,5vw,42px)">AI Classroom Cheating Detection System</h1>
<p class="lead mb-3" style="font-size:clamp(15px,2.4vw,18px);color:#E2E8F0">Real-Time Exam Surveillance Using Computer Vision and Behavioral Analysis</p>
<p class="mb-4" style="font-size:14px;color:#CBD5E1;line-height:1.6">AI-assisted recorded and live examination monitoring with computer vision, explainable events, protected evidence, and authorized human review.</p>
<div class="d-flex flex-wrap gap-2 mb-3">
@auth
<a href="{{ route('dashboard') }}" class="btn btn-primary"><i class="bi bi-speedometer2 me-1" aria-hidden="true"></i> Open Dashboard</a>
@else
<a href="{{ route('login') }}" class="btn btn-primary"><i class="bi bi-box-arrow-in-right me-1" aria-hidden="true"></i> Log In</a>
@if(Route::has('register'))
<a href="{{ route('register') }}" class="btn btn-outline-light">Create Account</a>
@endif
@endauth
<a href="{{ route('help.index') }}" class="btn btn-outline-light"><i class="bi bi-journal-text me-1" aria-hidden="true"></i> View Documentation</a>
<a href="https://github.com/Md-Moklesar-Rahman-Bappy/AI-Classroom-Cheating-Detection-System" target="_blank" rel="noopener" class="btn btn-outline-light"><i class="bi bi-github me-1" aria-hidden="true"></i> View Repository</a>
</div>
<div class="d-inline-flex align-items-center gap-2 px-3 py-1 rounded-pill" style="background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.12);font-size:11px;letter-spacing:.06em;text-transform:uppercase;color:#E2E8F0"><i class="bi bi-flask" aria-hidden="true"></i> Research Prototype — Not production-ready</div>
<div class="d-flex flex-wrap gap-3 mt-3" style="font-size:12px;color:#94A3B8">
<span><i class="bi bi-check-circle text-success me-1" aria-hidden="true"></i> Offline + Live</span>
<span><i class="bi bi-check-circle text-success me-1" aria-hidden="true"></i> Human review required</span>
<span><i class="bi bi-check-circle text-success me-1" aria-hidden="true"></i> Audit trail</span>
</div>
</div>
<div class="col-lg-6">
<div class="surveillance-mock shadow-lg hero-illustration" role="img" aria-label="Surveillance feed illustration showing person and phone detections">
<div class="mock-header"><span class="mock-dot" style="background:#EF4444" aria-hidden="true"></span><span class="mock-dot" style="background:#EAB308" aria-hidden="true"></span><span class="mock-dot" style="background:var(--success)" aria-hidden="true"></span><span class="ms-2" style="font-size:11px;color:#94A3B8"><i class="bi bi-camera-video me-1" aria-hidden="true"></i> Live feed · Room A-101 · 12 students</span><span class="ms-auto badge bg-danger" style="font-size:10px"><i class="bi bi-record-circle me-1" aria-hidden="true"></i> REC</span></div>
<div style="height:280px;background:linear-gradient(135deg,#1e293b 0%,#0f172a 100%);position:relative;display:flex;align-items:center;justify-content:center;overflow:hidden">
<div style="position:absolute;inset:12px;border:1px dashed rgba(148,163,184,.25);border-radius:8px"></div>
<div style="position:absolute;left:18%;top:22%;width:22%;height:52%;border:2px solid var(--success);border-radius:6px;display:flex;align-items:flex-start;justify-content:space-between;padding:3px"><span class="badge bg-success" style="font-size:9px">person 0.92</span></div>
<div style="position:absolute;left:48%;top:18%;width:20%;height:48%;border:2px solid var(--success);border-radius:6px;display:flex;align-items:flex-start;padding:3px"><span class="badge bg-success" style="font-size:9px">person 0.88</span></div>
<div style="position:absolute;left:42%;top:55%;width:10%;height:10%;border:2px solid var(--danger);border-radius:4px"><span class="badge bg-danger" style="font-size:8px;position:absolute;top:-14px;left:0">phone 0.87</span></div>
<div style="position:absolute;right:14%;top:28%;width:18%;height:42%;border:2px solid #F59E0B;border-radius:6px"><span class="badge bg-warning text-dark" style="font-size:8px;position:absolute;top:-14px;left:0">B1 looking left</span></div>
<div class="text-center" style="position:absolute;bottom:14px;left:50%;transform:translateX(-50%);background:rgba(15,23,42,.92);border:1px solid rgba(255,255,255,.1);border-radius:999px;padding:4px 10px;font-size:11px;color:#E2E8F0"><i class="bi bi-activity me-1 text-warning" aria-hidden="true"></i> 3 events pending review</div>
</div>
<div class="d-flex justify-content-between align-items-center p-2" style="background:var(--sidebar);border-top:1px solid rgba(255,255,255,.08);font-size:11px;color:#94A3B8">
<span><i class="bi bi-clock me-1" aria-hidden="true"></i> 00:14:22 · 24 FPS</span><span class="d-flex gap-2"><span class="badge bg-success">person</span><span class="badge bg-danger">phone</span><span class="badge bg-warning text-dark">behavior</span></span>
</div>
</div>
<div class="ai-notice mt-3" role="note" aria-label="Responsible AI notice"><i class="bi bi-shield-exclamation me-1" aria-hidden="true"></i><strong>AI Notice:</strong> AI-generated alerts indicate observable events that require human review. An alert is not proof of academic misconduct. Final academic or disciplinary decisions remain with authorized human reviewers and the institution.</div>
</div>
</div>
</div>
</section>
<section id="features" class="py-5">
<div class="container" style="max-width:1200px">
<div class="text-center mb-4"><div class="section-label mb-2"><i class="bi bi-stars me-1" aria-hidden="true"></i> Capabilities</div><h2 style="font-weight:700;letter-spacing:-.02em">Built for responsible exam surveillance</h2><p class="text-muted mx-auto" style="max-width:680px;font-size:14px">Offline recorded analysis and live monitoring with detection, tracking, behavioral analysis and evidence — all under human oversight.</p></div>
<div class="row g-3">
<div class="col-md-6 col-lg-3"><div class="card p-3 h-100 feature-card"><div class="d-flex gap-3"><div class="icon-box" style="background:#DBEAFE;color:var(--primary)"><i class="bi bi-collection-play" aria-hidden="true"></i></div><div><h3 style="font-size:14px;font-weight:600;margin-bottom:4px">Recorded Video Analysis</h3><p class="text-muted mb-0" style="font-size:13px">Upload exam video, process frame-by-frame, generate annotated output and timeline.</p></div></div></div></div>
<div class="col-md-6 col-lg-3"><div class="card p-3 h-100 feature-card"><div class="d-flex gap-3"><div class="icon-box" style="background:#EDE9FE;color:var(--secondary)"><i class="bi bi-broadcast" aria-hidden="true"></i></div><div><h3 style="font-size:14px;font-weight:600;margin-bottom:4px">Live Monitoring</h3><p class="text-muted mb-0" style="font-size:13px">Webcam and test-stream live surveillance with real-time alert queue.</p></div></div></div></div>
<div class="col-md-6 col-lg-3"><div class="card p-3 h-100 feature-card"><div class="d-flex gap-3"><div class="icon-box" style="background:#FEE2E2;color:var(--danger)"><i class="bi bi-person-bounding-box" aria-hidden="true"></i></div><div><h3 style="font-size:14px;font-weight:600;margin-bottom:4px">Person and Mobile Phone Detection</h3><p class="text-muted mb-0" style="font-size:13px">YOLO11n person and phone detection (D1/D2) with confidence and bbox.</p></div></div></div></div>
<div class="col-md-6 col-lg-3"><div class="card p-3 h-100 feature-card"><div class="d-flex gap-3"><div class="icon-box" style="background:#CCFBF1;color:var(--teal)"><i class="bi bi-arrows-move" aria-hidden="true"></i></div><div><h3 style="font-size:14px;font-weight:600;margin-bottom:4px">Anonymous Tracking</h3><p class="text-muted mb-0" style="font-size:13px">ByteTrack/DeepSORT anonymous tracking without facial identity.</p></div></div></div></div>
<div class="col-md-6 col-lg-3"><div class="card p-3 h-100 feature-card"><div class="d-flex gap-3"><div class="icon-box" style="background:#FEF3C7;color:var(--warning)"><i class="bi bi-clock-history" aria-hidden="true"></i></div><div><h3 style="font-size:14px;font-weight:600;margin-bottom:4px">Temporal Event Rules</h3><p class="text-muted mb-0" style="font-size:13px">B1–B4 rules: repeated looking left/right/back, leaving seat with thresholds.</p></div></div></div></div>
<div class="col-md-6 col-lg-3"><div class="card p-3 h-100 feature-card"><div class="d-flex gap-3"><div class="icon-box" style="background:#DCFCE7;color:var(--success)"><i class="bi bi-file-earmark-bar-graph" aria-hidden="true"></i></div><div><h3 style="font-size:14px;font-weight:600;margin-bottom:4px">Evidence and Human Review</h3><p class="text-muted mb-0" style="font-size:13px">Snapshots, optional clips, reviewer workflow: dismiss / needs review / confirmed.</p></div></div></div></div>
<div class="col-md-6 col-lg-3"><div class="card p-3 h-100 feature-card"><div class="d-flex gap-3"><div class="icon-box" style="background:#E0F2FE;color:#0284C7"><i class="bi bi-graph-up" aria-hidden="true"></i></div><div><h3 style="font-size:14px;font-weight:600;margin-bottom:4px">Metrics and Reports</h3><p class="text-muted mb-0" style="font-size:13px">Processing FPS, latency, event breakdowns, exportable reports.</p></div></div></div></div>
<div class="col-md-6 col-lg-3"><div class="card p-3 h-100 feature-card"><div class="d-flex gap-3"><div class="icon-box" style="background:#F1F5F9;color:var(--muted)"><i class="bi bi-journal-text" aria-hidden="true"></i></div><div><h3 style="font-size:14px;font-weight:600;margin-bottom:4px">Audit Logging</h3><p class="text-muted mb-0" style="font-size:13px">Tamper-evident logs for access, reviews, and retention actions.</p></div></div></div></div>
</div>
</div>
</section>
<section id="how-it-works" class="py-5" style="background:var(--surface);border-top:1px solid var(--border);border-bottom:1px solid var(--border)">
<div class="container" style="max-width:1200px">
<div class="text-center mb-4"><div class="section-label mb-2"><i class="bi bi-diagram-3 me-1" aria-hidden="true"></i> How It Works</div><h2 style="font-weight:700;letter-spacing:-.02em">Video or Camera → Report</h2></div>
<div class="row g-2 align-items-center">
<div class="col-6 col-md-2"><div class="flow-step"><i class="bi bi-camera-video text-primary" style="font-size:22px" aria-hidden="true"></i><div style="font-size:12px;font-weight:700;margin-top:6px">Video or Camera</div><div class="text-muted" style="font-size:11px">File · Webcam · RTSP</div></div></div>
<div class="col-1 d-none d-md-flex flow-arrow"><i class="bi bi-arrow-right" aria-hidden="true"></i></div>
<div class="col-6 col-md-2"><div class="flow-step"><i class="bi bi-cpu text-primary" style="font-size:22px" aria-hidden="true"></i><div style="font-size:12px;font-weight:700;margin-top:6px">AI Processing</div><div class="text-muted" style="font-size:11px">YOLO + Tracking</div></div></div>
<div class="col-1 d-none d-md-flex flow-arrow"><i class="bi bi-arrow-right" aria-hidden="true"></i></div>
<div class="col-6 col-md-2"><div class="flow-step"><i class="bi bi-activity text-warning" style="font-size:22px" aria-hidden="true"></i><div style="font-size:12px;font-weight:700;margin-top:6px">Observable Events</div><div class="text-muted" style="font-size:11px">D1/D2/B1–B4</div></div></div>
<div class="col-1 d-none d-md-flex flow-arrow"><i class="bi bi-arrow-right" aria-hidden="true"></i></div>
<div class="col-6 col-md-2"><div class="flow-step"><i class="bi bi-images text-success" style="font-size:22px" aria-hidden="true"></i><div style="font-size:12px;font-weight:700;margin-top:6px">Protected Evidence</div><div class="text-muted" style="font-size:11px">Snapshot/Clip</div></div></div>
<div class="w-100 d-md-none"></div>
<div class="col-1 d-none d-md-flex flow-arrow"><i class="bi bi-arrow-right" aria-hidden="true"></i></div>
<div class="col-6 col-md-2"><div class="flow-step" style="border-color:var(--primary);background:#EFF6FF"><i class="bi bi-person-check text-primary" style="font-size:22px" aria-hidden="true"></i><div style="font-size:12px;font-weight:700;margin-top:6px">Human Review</div><div class="text-muted" style="font-size:11px">Authorized decision</div></div></div>
<div class="col-1 d-none d-md-flex flow-arrow"><i class="bi bi-arrow-right" aria-hidden="true"></i></div>
<div class="col-6 col-md-2"><div class="flow-step"><i class="bi bi-file-text text-muted" style="font-size:22px" aria-hidden="true"></i><div style="font-size:12px;font-weight:700;margin-top:6px">Report</div><div class="text-muted" style="font-size:11px">Export + Audit</div></div></div>
</div>
<div class="text-center text-muted mt-3" style="font-size:11px">FastAPI AI service (8001) ↔ Laravel Dashboard — MySQL · RBAC · Encrypted storage · Rate limiting</div>
</div>
</section>
<section id="responsible-ai" class="py-5">
<div class="container" style="max-width:1200px">
<div class="row g-4 align-items-start">
<div class="col-lg-6">
<div class="section-label mb-2"><i class="bi bi-shield-check me-1" aria-hidden="true"></i> Responsible AI</div>
<h2 style="font-weight:700;letter-spacing:-.02em">AI flags, humans decide</h2>
<div class="card p-3 mt-3" style="background:#fffbeb;border-left:4px solid var(--warning)">
<p class="mb-0" style="font-size:13px;color:#78350f"><i class="bi bi-exclamation-triangle me-1" aria-hidden="true"></i> “AI-generated alerts indicate observable events that require human review. An alert is not proof of academic misconduct. Final academic or disciplinary decisions remain with authorized human reviewers and the institution.”</p>
</div>
<ul class="mt-3 mb-0" style="font-size:13px;color:var(--muted);line-height:1.7">
<li>No facial recognition or emotion inference</li>
<li>No protected-characteristic inference</li>
<li>No automatic disciplinary action</li>
<li>Evidence access is role-based and audited</li>
</ul>
</div>
<div class="col-lg-6">
<div class="card p-0 overflow-hidden">
<div class="card-header bg-white d-flex justify-content-between align-items-center"><span style="font-size:12px;font-weight:700;letter-spacing:.06em;text-transform:uppercase"><i class="bi bi-list-check me-1 text-success" aria-hidden="true"></i> Review workflow</span><span class="badge bg-light text-dark border" style="font-size:11px">Text + color + icon</span></div>
<div class="p-3">
<ol class="mb-0" style="font-size:13px;line-height:1.8">
<li><span class="badge bg-warning text-dark">Alert</span> Event D2/B* created with frame, confidence, evidence_available</li>
<li><span class="badge bg-primary">Evidence</span> Reviewer opens snapshot/clip + track history</li>
<li><span class="badge bg-success">Dismissed Normal</span> / <span class="badge bg-warning text-dark">Needs Further Review</span> / <span class="badge bg-danger">Confirmed Suspicious</span></li>
<li><span class="badge bg-dark">Audit</span> Decision logged with reviewer, time, note</li>
</ol>
</div>
<div class="p-3" style="background:var(--bg);border-top:1px solid var(--border);font-size:12px;color:var(--muted)"><i class="bi bi-info-circle me-1" aria-hidden="true"></i> All statuses use text + color + icon — never color alone.</div>
</div>
</div>
</div>
</div>
</section>
<section class="py-5" style="background:var(--surface);border-top:1px solid var(--border);border-bottom:1px solid var(--border)">
<div class="container" style="max-width:1200px">
<div class="text-center mb-4"><div class="section-label mb-2"><i class="bi bi-clipboard-check me-1" aria-hidden="true"></i> Implementation status</div><h2 style="font-weight:700;letter-spacing:-.02em">What is verified today</h2></div>
<div class="row g-3">
<div class="col-md-6 col-lg-3"><div class="card p-3 h-100"><div class="d-flex align-items-center gap-2 mb-2"><span class="badge bg-success"><i class="bi bi-check-lg" aria-hidden="true"></i> Verified</span><span style="font-size:13px;font-weight:600">Recorded mode</span></div><p class="text-muted mb-0" style="font-size:13px">Upload, processing, annotated output and event review verified via tests and runtime evidence.</p></div></div>
<div class="col-md-6 col-lg-3"><div class="card p-3 h-100"><div class="d-flex align-items-center gap-2 mb-2"><span class="badge bg-success"><i class="bi bi-check-lg" aria-hidden="true"></i> Verified</span><span style="font-size:13px;font-weight:600">Webcam / test live mode</span></div><p class="text-muted mb-0" style="font-size:13px">Webcam and test-source live streaming verified per current repository evidence.</p></div></div>
<div class="col-md-6 col-lg-3"><div class="card p-3 h-100"><div class="d-flex align-items-center gap-2 mb-2"><span class="badge bg-secondary"><i class="bi bi-question-lg" aria-hidden="true"></i> Unverified</span><span style="font-size:13px;font-weight:600">EZVIZ RTSP/ONVIF</span></div><p class="text-muted mb-0" style="font-size:13px">Direct EZVIZ RTSP/ONVIF integration remains unverified unless a current source proves otherwise.</p></div></div>
<div class="col-md-6 col-lg-3"><div class="card p-3 h-100"><div class="d-flex align-items-center gap-2 mb-2"><span class="badge bg-warning text-dark"><i class="bi bi-pause" aria-hidden="true"></i> Pending</span><span style="font-size:13px;font-weight:600">Real-participant evaluation</span></div><p class="text-muted mb-0" style="font-size:13px">Blocked or pending per current data-governance and consent status.</p></div></div>
</div>
<div class="text-center text-muted mt-3" style="font-size:11px">See docs/IMPLEMENTATION_STATUS.md and QA reports for evidence.</div>
</div>
</section>
<section id="research" class="py-5">
<div class="container" style="max-width:1200px">
<div class="text-center mb-4"><div class="section-label mb-2"><i class="bi bi-mortarboard me-1" aria-hidden="true"></i> Research contributions</div><h2 style="font-weight:700;letter-spacing:-.02em">Contribution to examination integrity</h2></div>
<div class="row g-3">
<div class="col-md-6 col-lg-4"><div class="card p-3 h-100"><div class="d-flex gap-2 mb-2"><span class="badge bg-primary">01</span><span style="font-size:13px;font-weight:600">AI-assisted framework</span></div><p class="text-muted mb-0" style="font-size:13px">Unified pipeline for recorded and live video — detection, tracking, behavioral rules, evidence.</p></div></div>
<div class="col-md-6 col-lg-4"><div class="card p-3 h-100"><div class="d-flex gap-2 mb-2"><span class="badge bg-primary">02</span><span style="font-size:13px;font-weight:600">Lightweight deployment</span></div><p class="text-muted mb-0" style="font-size:13px">YOLO11n with measured low-resource profile for constrained institutional hardware.</p></div></div>
<div class="col-md-6 col-lg-4"><div class="card p-3 h-100"><div class="d-flex gap-2 mb-2"><span class="badge bg-primary">03</span><span style="font-size:13px;font-weight:600">Human-review workflow</span></div><p class="text-muted mb-0" style="font-size:13px">Event → evidence → reviewer decision with audit trail and retention controls.</p></div></div>
<div class="col-md-6 col-lg-6"><div class="card p-3 h-100"><div class="d-flex gap-2 mb-2"><span class="badge bg-primary">04</span><span style="font-size:13px;font-weight:600">Practical event taxonomy</span></div><p class="text-muted mb-0" style="font-size:13px">D1/D2 and B1–B4 with thresholds evaluated on classroom recordings.</p></div></div>
<div class="col-md-6 col-lg-6"><div class="card p-3 h-100"><div class="d-flex gap-2 mb-2"><span class="badge bg-primary">05</span><span style="font-size:13px;font-weight:600">Responsible AI stance</span></div><p class="text-muted mb-0" style="font-size:13px">No facial recognition, no auto-accusation, explicit notice and human determination.</p></div></div>
</div>
</div>
</section>
</main>
<footer class="footer-dark py-4">
<div class="container" style="max-width:1200px">
<div class="row g-4">
<div class="col-md-5">
<div class="d-flex align-items-center gap-2 mb-2"><span class="bg-primary rounded d-flex align-items-center justify-content-center" style="width:28px;height:28px" aria-hidden="true"><i class="bi bi-shield-lock text-white" style="font-size:14px" aria-hidden="true"></i></span><strong style="color:#fff">AI Classroom Cheating Detection System</strong><span class="badge bg-light text-dark border ms-2" style="font-size:10px">Research Prototype</span></div>
<p class="mb-1" style="font-size:12px;color:#CBD5E1">Md Moklesar Rahman — Master of Science (MSc) in IT</p>
<p class="mb-1" style="font-size:12px;color:#94A3B8">Jahangirnagar University · Supervisor: Risala Tasin Khan, PhD</p>
<p class="mb-2" style="font-size:11px;color:#64748B"><i class="bi bi-shield-exclamation me-1 text-warning" aria-hidden="true"></i> AI alerts require human review — not proof of misconduct.</p>
<p class="mb-0" style="font-size:11px;color:#64748B">Version {{ config("app.version","1.0") }} · © 2026</p>
</div>
<div class="col-md-3">
<div style="font-size:11px;letter-spacing:.08em;text-transform:uppercase;color:#CBD5E1;font-weight:700;margin-bottom:8px">Links</div>
<ul class="list-unstyled mb-0" style="font-size:13px">
<li><a href="{{ route('login') }}"><i class="bi bi-box-arrow-in-right me-1" aria-hidden="true"></i> Login</a></li>
@if(Route::has('register'))<li><a href="{{ route('register') }}"><i class="bi bi-person-plus me-1" aria-hidden="true"></i> Register</a></li>@endif
<li><a href="https://laravel.com/docs" target="_blank" rel="noopener"><i class="bi bi-journal-text me-1" aria-hidden="true"></i> Documentation</a></li>
<li><a href="https://github.com/Md-Moklesar-Rahman-Bappy/AI-Classroom-Cheating-Detection-System" target="_blank" rel="noopener"><i class="bi bi-github me-1" aria-hidden="true"></i> Repository</a></li>
</ul>
</div>
<div class="col-md-4">
<div style="font-size:11px;letter-spacing:.08em;text-transform:uppercase;color:#CBD5E1;font-weight:700;margin-bottom:8px">Responsible use</div>
<p style="font-size:12px;color:#94A3B8;line-height:1.6">For authorized academic evaluation only. Do not use alerts as sole basis for disciplinary action. Evidence retention and access follow institutional policy.</p>
</div>
</div>
</div>
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
