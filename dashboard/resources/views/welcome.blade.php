<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>AI Classroom Cheating Detection System — Real-Time Exam Surveillance</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400&display=swap" rel="stylesheet">
<style>
:root{--sidebar:#0F172A;--primary:#2563EB;--primary-dark:#1D4ED8;--success:#22C55E;--danger:#EF4444;--warning:#D97706;--bg:#F8FAFC;--surface:#FFFFFF;--border:#E2E8F0;--text:#172033;--muted:#64748B;--subtle:#94A3B8;--radius-md:12px;--radius-lg:16px;--shadow-sm:0 1px 2px rgba(15,23,42,.06);--shadow-md:0 4px 12px rgba(15,23,42,.08)}
*{font-family:Inter,system-ui,sans-serif}body{background:var(--bg);color:var(--text);margin:0}
.navbar-blur{backdrop-filter:blur(8px);background:rgba(255,255,255,.9);border-bottom:1px solid var(--border)}
.btn-primary{background:var(--primary);border-color:var(--primary);font-weight:600}.btn-primary:hover{background:var(--primary-dark);border-color:var(--primary-dark)}
.hero{background:var(--sidebar);color:#CBD5E1;overflow:hidden;position:relative}
.hero h1{color:#fff;font-weight:800;letter-spacing:-.03em;line-height:1.05}
.hero .sub{color:#94A3B8}
.card{border:1px solid var(--border);border-radius:var(--radius-md);box-shadow:var(--shadow-sm);background:var(--surface)}
.section-label{font-size:11px;letter-spacing:.08em;text-transform:uppercase;color:var(--muted);font-weight:700}
.icon-box{width:40px;height:40px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:18px;flex-shrink:0}
.feature-card:hover{box-shadow:var(--shadow-md);transform:translateY(-1px);transition:all .15s}
.arch-step{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius-md);padding:14px;text-align:center;position:relative}
.arch-arrow{color:var(--muted)}
.timeline{position:relative;padding-left:28px;border-left:2px solid var(--border)}
.timeline-item{position:relative;margin-bottom:20px}
.timeline-dot{position:absolute;left:-37px;top:2px;width:16px;height:16px;border-radius:50%;border:3px solid var(--surface);box-shadow:0 0 0 2px var(--primary)}
.ai-notice{background:#fffbeb;border-left:4px solid var(--warning);padding:12px 16px;border-radius:8px;font-size:13px;color:#92400e}
.footer-dark{background:var(--sidebar);color:#94A3B8}
.footer-dark a{color:#CBD5E1;text-decoration:none}.footer-dark a:hover{color:#fff}
.surveillance-mock{border:1px solid rgba(255,255,255,.12);border-radius:12px;overflow:hidden;background:#0B1224}
.mock-header{height:32px;background:#172033;display:flex;align-items:center;gap:6px;padding:0 10px}
.mock-dot{width:8px;height:8px;border-radius:50%}
</style>
</head>
<body>
<nav class="navbar navbar-expand-lg sticky-top navbar-blur py-2">
<div class="container" style="max-width:1200px">
<a class="navbar-brand d-flex align-items-center gap-2" href="/">
<span class="bg-primary rounded d-flex align-items-center justify-content-center" style="width:30px;height:30px"><i class="bi bi-shield-lock text-white" aria-hidden="true"></i></span>
<span style="font-weight:700;letter-spacing:-.02em;color:var(--sidebar)">AI Classroom</span><span class="d-none d-sm-inline" style="font-size:11px;letter-spacing:.08em;text-transform:uppercase;color:var(--muted);margin-left:4px">Surveillance Platform</span>
</a>
<button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMain" aria-label="Toggle navigation"><span class="navbar-toggler-icon"></span></button>
<div class="collapse navbar-collapse" id="navMain">
<ul class="navbar-nav ms-auto align-items-lg-center gap-lg-2">
<li class="nav-item"><a class="nav-link" href="#features">Features</a></li>
<li class="nav-item"><a class="nav-link" href="#architecture">Architecture</a></li>
<li class="nav-item"><a class="nav-link" href="#how-it-works">How It Works</a></li>
<li class="nav-item"><a class="nav-link" href="#research">Research</a></li>
@auth<a href="{{ route('dashboard') }}" class="btn btn-primary btn-sm ms-lg-2"><i class="bi bi-speedometer2 me-1"></i> Dashboard</a>
@else
<a href="{{ route('login') }}" class="btn btn-outline-primary btn-sm ms-lg-2">Login</a>
<a href="{{ route('register') }}" class="btn btn-primary btn-sm">Register</a>
@endauth
</ul>
</div>
</div>
</nav>
<section class="hero py-5 py-lg-5">
<div class="container" style="max-width:1200px">
<div class="row align-items-center g-4">
<div class="col-lg-6">
<div class="d-inline-flex align-items-center gap-2 mb-3" style="background:rgba(37,99,235,.15);border:1px solid rgba(37,99,235,.3);border-radius:999px;padding:4px 12px;font-size:11px;letter-spacing:.06em;text-transform:uppercase;color:#93C5FD"><i class="bi bi-cpu"></i> Computer Vision + Behavioral Analysis <span class="badge bg-success" style="font-size:10px">Research Prototype</span></div>
<h1 class="display-5 mb-3" style="font-size:clamp(28px,5vw,42px)">AI Classroom Cheating Detection System</h1>
<p class="lead sub mb-4" style="font-size:clamp(15px,2.5vw,18px)">Real-Time Exam Surveillance Using Computer Vision and Behavioral Analysis</p>
<p class="mb-4" style="font-size:14px;color:#CBD5E1;line-height:1.6">AI-assisted examination surveillance that detects observable events — person, mobile phone, orientation and movement — flags them for human review, and preserves evidence. Alerts are not proof of misconduct.</p>
<div class="d-flex flex-wrap gap-2 mb-4">
@auth
<a href="{{ route('dashboard') }}" class="btn btn-primary"><i class="bi bi-box-arrow-in-right me-1"></i> Go to Dashboard</a>
@else
<a href="{{ route('login') }}" class="btn btn-primary"><i class="bi bi-box-arrow-in-right me-1"></i> Login</a>
<a href="{{ route('register') }}" class="btn btn-outline-light">Register</a>
@endauth
<a href="https://laravel.com/docs" target="_blank" rel="noopener" class="btn btn-outline-light"><i class="bi bi-journal-text me-1"></i> Documentation</a>
<a href="https://github.com/Md-Moklesar-Rahman-Bappy/AI-Classroom-Cheating-Detection-System" target="_blank" rel="noopener" class="btn btn-outline-light"><i class="bi bi-github me-1"></i> GitHub Repository</a>
</div>
<div class="d-flex flex-wrap gap-3" style="font-size:12px;color:#94A3B8">
<span><i class="bi bi-check-circle text-success me-1"></i> Offline + Live modes</span>
<span><i class="bi bi-check-circle text-success me-1"></i> Human review required</span>
<span><i class="bi bi-check-circle text-success me-1"></i> Audit trail</span>
</div>
</div>
<div class="col-lg-6">
<div class="surveillance-mock shadow-lg">
<div class="mock-header"><span class="mock-dot" style="background:#EF4444"></span><span class="mock-dot" style="background:#EAB308"></span><span class="mock-dot" style="background:#22C55E"></span><span class="ms-2" style="font-size:11px;color:#94A3B8"><i class="bi bi-camera-video me-1"></i> Live feed · Room A-101 · 12 students</span><span class="ms-auto badge bg-danger" style="font-size:10px"><i class="bi bi-record-circle me-1"></i> REC</span></div>
<div style="height:280px;background:linear-gradient(135deg,#1e293b 0%,#0f172a 100%);position:relative;display:flex;align-items:center;justify-content:center;overflow:hidden">
<div style="position:absolute;inset:12px;border:1px dashed rgba(148,163,184,.25);border-radius:8px"></div>
<div style="position:absolute;left:18%;top:22%;width:22%;height:52%;border:2px solid #22C55E;border-radius:6px;display:flex;align-items:flex-start;justify-content:space-between;padding:3px"><span class="badge bg-success" style="font-size:9px">person 0.92</span><span style="width:6px;height:6px;background:#22C55E;border-radius:50%"></span></div>
<div style="position:absolute;left:48%;top:18%;width:20%;height:48%;border:2px solid #22C55E;border-radius:6px;display:flex;align-items:flex-start;justify-content:space-between;padding:3px"><span class="badge bg-success" style="font-size:9px">person 0.88</span></div>
<div style="position:absolute;left:42%;top:55%;width:10%;height:10%;border:2px solid #EF4444;border-radius:4px"><span class="badge bg-danger" style="font-size:8px;position:absolute;top:-14px;left:0">phone 0.87</span></div>
<div style="position:absolute;right:14%;top:28%;width:18%;height:42%;border:2px solid #F59E0B;border-radius:6px"><span class="badge bg-warning text-dark" style="font-size:8px;position:absolute;top:-14px;left:0">B1 looking left</span></div>
<div class="text-center" style="position:absolute;bottom:14px;left:50%;transform:translateX(-50%);background:rgba(15,23,42,.9);border:1px solid rgba(255,255,255,.1);border-radius:999px;padding:4px 10px;font-size:11px;color:#E2E8F0"><i class="bi bi-activity me-1 text-warning"></i> 3 events pending review</div>
</div>
<div class="d-flex justify-content-between align-items-center p-2" style="background:#0F172A;border-top:1px solid rgba(255,255,255,.08);font-size:11px;color:#94A3B8">
<span><i class="bi bi-clock me-1"></i> 00:14:22 · 24 FPS</span><span class="d-flex gap-2"><span class="badge bg-success">person</span><span class="badge bg-danger">phone</span><span class="badge bg-warning text-dark">behavior</span></span>
</div>
</div>
<div class="ai-notice mt-3"><i class="bi bi-shield-exclamation me-1"></i><strong>AI Notice:</strong> Alerts indicate observable events, not proof of cheating. Human review required.</div>
</div>
</div>
</div>
</section>
<section id="features" class="py-5">
<div class="container" style="max-width:1200px">
<div class="text-center mb-4"><div class="section-label mb-2"><i class="bi bi-stars me-1"></i> Features</div><h2 style="font-weight:700;letter-spacing:-.02em">Built for responsible exam surveillance</h2><p class="text-muted mx-auto" style="max-width:640px;font-size:14px">Offline recorded analysis and live monitoring with detection, tracking, behavioral analysis and evidence — all under human oversight.</p></div>
<div class="row g-3">
<div class="col-md-6 col-lg-4"><div class="card p-3 h-100 feature-card"><div class="d-flex gap-3"><div class="icon-box" style="background:#DBEAFE;color:#2563EB"><i class="bi bi-person-bounding-box"></i></div><div><h3 style="font-size:14px;font-weight:600;margin-bottom:4px">Person Detection</h3><p class="text-muted mb-0" style="font-size:13px">YOLO-based detection of students in exam halls, with bounding boxes and confidence scores.</p></div></div></div></div>
<div class="col-md-6 col-lg-4"><div class="card p-3 h-100 feature-card"><div class="d-flex gap-3"><div class="icon-box" style="background:#FEE2E2;color:#EF4444"><i class="bi bi-phone"></i></div><div><h3 style="font-size:14px;font-weight:600;margin-bottom:4px">Mobile Phone Detection</h3><p class="text-muted mb-0" style="font-size:13px">Flags visible phones inside monitored area as D2 events requiring review.</p></div></div></div></div>
<div class="col-md-6 col-lg-4"><div class="card p-3 h-100 feature-card"><div class="d-flex gap-3"><div class="icon-box" style="background:#EDE9FE;color:#7C3AED"><i class="bi bi-arrows-move"></i></div><div><h3 style="font-size:14px;font-weight:600;margin-bottom:4px">Tracking & Seat Analysis</h3><p class="text-muted mb-0" style="font-size:13px">ByteTrack / DeepSORT tracking, seat-leaving and prolonged absence detection.</p></div></div></div></div>
<div class="col-md-6 col-lg-4"><div class="card p-3 h-100 feature-card"><div class="d-flex gap-3"><div class="icon-box" style="background:#FEF3C7;color:#D97706"><i class="bi bi-eye"></i></div><div><h3 style="font-size:14px;font-weight:600;margin-bottom:4px">Head Orientation</h3><p class="text-muted mb-0" style="font-size:13px">Detects repeated looking left/right/back patterns (B1-B3) via pose analysis.</p></div></div></div></div>
<div class="col-md-6 col-lg-4"><div class="card p-3 h-100 feature-card"><div class="d-flex gap-3"><div class="icon-box" style="background:#DCFCE7;color:#16A34A"><i class="bi bi-collection-play"></i></div><div><h3 style="font-size:14px;font-weight:600;margin-bottom:4px">Recorded & Live Modes</h3><p class="text-muted mb-0" style="font-size:13px">Upload video files or connect RTSP/webcam streams with live dashboard rendering.</p></div></div></div></div>
<div class="col-md-6 col-lg-4"><div class="card p-3 h-100 feature-card"><div class="d-flex gap-3"><div class="icon-box" style="background:#CCFBF1;color:#0F766E"><i class="bi bi-file-earmark-bar-graph"></i></div><div><h3 style="font-size:14px;font-weight:600;margin-bottom:4px">Evidence & Audit</h3><p class="text-muted mb-0" style="font-size:13px">Snapshots, clips, event timeline, review decisions and immutable audit logs.</p></div></div></div></div>
</div>
</div>
</section>
<section id="architecture" class="py-5" style="background:var(--surface);border-top:1px solid var(--border);border-bottom:1px solid var(--border)">
<div class="container" style="max-width:1200px">
<div class="text-center mb-4"><div class="section-label mb-2"><i class="bi bi-diagram-3 me-1"></i> Architecture Overview</div><h2 style="font-weight:700;letter-spacing:-.02em">From video source to human decision</h2></div>
<div class="row g-2 align-items-center mb-4">
<div class="col-6 col-md-2"><div class="arch-step"><i class="bi bi-camera-video text-primary" style="font-size:20px"></i><div style="font-size:12px;font-weight:600;margin-top:6px">Video Source</div><div class="text-muted" style="font-size:11px">File / RTSP / Webcam</div></div></div>
<div class="col-1 d-none d-md-flex justify-content-center"><i class="bi bi-arrow-right arch-arrow"></i></div>
<div class="col-6 col-md-2"><div class="arch-step"><i class="bi bi-cpu text-primary" style="font-size:20px"></i><div style="font-size:12px;font-weight:600;margin-top:6px">Detection</div><div class="text-muted" style="font-size:11px">YOLO11n + Pose</div></div></div>
<div class="col-1 d-none d-md-flex justify-content-center"><i class="bi bi-arrow-right arch-arrow"></i></div>
<div class="col-6 col-md-2"><div class="arch-step"><i class="bi bi-bullseye text-warning" style="font-size:20px"></i><div style="font-size:12px;font-weight:600;margin-top:6px">Tracking</div><div class="text-muted" style="font-size:11px">ByteTrack</div></div></div>
<div class="col-1 d-none d-md-flex justify-content-center"><i class="bi bi-arrow-right arch-arrow"></i></div>
<div class="col-6 col-md-2"><div class="arch-step"><i class="bi bi-activity text-danger" style="font-size:20px"></i><div style="font-size:12px;font-weight:600;margin-top:6px">Behavior</div><div class="text-muted" style="font-size:11px">Event Engine B1-B4/D2</div></div></div>
</div>
<div class="row g-2 align-items-center">
<div class="col-6 col-md-2"><div class="arch-step"><i class="bi bi-images text-success" style="font-size:20px"></i><div style="font-size:12px;font-weight:600;margin-top:6px">Evidence</div><div class="text-muted" style="font-size:11px">Snapshot + Clip</div></div></div>
<div class="col-1 d-none d-md-flex justify-content-center"><i class="bi bi-arrow-right arch-arrow"></i></div>
<div class="col-6 col-md-2"><div class="arch-step"><i class="bi bi-window-stack text-primary" style="font-size:20px"></i><div style="font-size:12px;font-weight:600;margin-top:6px">Dashboard</div><div class="text-muted" style="font-size:11px">Laravel + Bootstrap</div></div></div>
<div class="col-1 d-none d-md-flex justify-content-center"><i class="bi bi-arrow-right arch-arrow"></i></div>
<div class="col-6 col-md-2"><div class="arch-step" style="border-color:var(--primary);background:#EFF6FF"><i class="bi bi-person-check text-primary" style="font-size:20px"></i><div style="font-size:12px;font-weight:600;margin-top:6px">Human Review</div><div class="text-muted" style="font-size:11px">Reviewer decision</div></div></div>
<div class="col-1 d-none d-md-flex justify-content-center"><i class="bi bi-arrow-right arch-arrow"></i></div>
<div class="col-6 col-md-2"><div class="arch-step"><i class="bi bi-journal-text text-muted" style="font-size:20px"></i><div style="font-size:12px;font-weight:600;margin-top:6px">Audit Log</div><div class="text-muted" style="font-size:11px">Tamper-evident</div></div></div>
</div>
<div class="text-center text-muted mt-3" style="font-size:11px">FastAPI AI service (8001) ↔ Laravel Dashboard — MySQL · RBAC · Encrypted storage · Rate limiting</div>
</div>
</section>
<section id="how-it-works" class="py-5">
<div class="container" style="max-width:1200px">
<div class="row g-4">
<div class="col-lg-6"><div class="card p-4 h-100"><div class="section-label mb-2"><i class="bi bi-play-circle me-1"></i> How It Works — Recorded Mode</div><h3 style="font-size:16px;font-weight:700">Recorded Video Analysis</h3><ol class="mt-3 mb-0" style="font-size:13px;line-height:1.8"><li><strong>Upload</strong> exam video to a session (exam room + time window)</li><li><strong>Process</strong> via analysis job — frame extraction → YOLO detection → tracking → behavior rules</li><li><strong>Generate</strong> annotated output video + evidence snapshots/clips</li><li><strong>Review</strong> event timeline — filter by D2 / B1-B4, confirm or dismiss with note</li><li><strong>Export</strong> report / audit trail for institutional process</li></ol></div></div>
<div class="col-lg-6"><div class="card p-4 h-100"><div class="section-label mb-2"><i class="bi bi-broadcast me-1"></i> How It Works — Live Mode</div><h3 style="font-size:16px;font-weight:700">Live Surveillance Monitoring</h3><ol class="mt-3 mb-0" style="font-size:13px;line-height:1.8"><li><strong>Connect</strong> IP camera / webcam as camera source, health-check RTSP/HTTP</li><li><strong>Stream</strong> frames to AI service — real-time detection with bounding-box overlay</li><li><strong>Alert</strong> queue — live dashboard shows events within ~200 ms target latency</li><li><strong>Review</strong> instantly or after session — same human workflow as recorded</li><li><strong>Retain</strong> evidence per policy, with role-based access</li></ol></div></div>
</div>
</div>
</section>
<section id="review-workflow" class="py-5" style="background:var(--surface);border-top:1px solid var(--border);border-bottom:1px solid var(--border)">
<div class="container" style="max-width:1200px">
<div class="row g-4 align-items-start">
<div class="col-lg-6"><div class="section-label mb-2"><i class="bi bi-eye me-1"></i> Human Review Workflow</div><h2 style="font-weight:700;letter-spacing:-.02em">AI flags, humans decide</h2><p class="text-muted" style="font-size:14px">Every detection is an observable event requiring review. The system never auto-accuses.</p><div class="timeline mt-4"><div class="timeline-item"><div class="timeline-dot" style="background:var(--warning)"></div><div style="font-size:13px;font-weight:600"><span class="badge bg-warning text-dark me-2">1</span> Alert generated</div><div class="text-muted" style="font-size:12px">Event D2/B* created with frame, confidence, bbox, evidence_available flag</div></div><div class="timeline-item"><div class="timeline-dot" style="background:var(--primary)"></div><div style="font-size:13px;font-weight:600"><span class="badge bg-primary me-2">2</span> Evidence review</div><div class="text-muted" style="font-size:12px">Reviewer opens event → snapshot/clip, track history, session context</div></div><div class="timeline-item"><div class="timeline-dot" style="background:var(--success)"></div><div style="font-size:13px;font-weight:600"><span class="badge bg-success me-2">3</span> Decision</div><div class="text-muted" style="font-size:12px"><span class="badge bg-success">Dismissed Normal</span> <span class="badge bg-warning text-dark">Needs Further Review</span> <span class="badge bg-danger">Confirmed Suspicious</span></div></div><div class="timeline-item"><div class="timeline-dot" style="background:var(--muted)"></div><div style="font-size:13px;font-weight:600"><span class="badge bg-dark me-2">4</span> Audit & report</div><div class="text-muted" style="font-size:12px">Decision logged with reviewer, timestamp, note; available in audit logs and reports</div></div></div></div>
<div class="col-lg-6"><div class="card p-0 overflow-hidden"><div class="card-header bg-white d-flex justify-content-between align-items-center"><span style="font-size:12px;font-weight:700;letter-spacing:.06em;text-transform:uppercase"><i class="bi bi-shield-check me-1 text-success"></i> Status semantics</span><span class="badge bg-light text-dark border" style="font-size:11px">Text + color + icon</span></div><div class="table-responsive"><table class="table table-sm mb-0" style="font-size:13px"><thead><tr style="font-size:11px;letter-spacing:.06em;text-transform:uppercase;color:var(--muted)"><th>Domain</th><th>Status</th><th>Meaning</th></tr></thead><tbody><tr><td>Detection</td><td><span class="badge bg-success">D1 normal</span></td><td class="text-muted">Person detected</td></tr><tr><td>Detection</td><td><span class="badge bg-danger">D2</span></td><td class="text-muted">Mobile phone visible</td></tr><tr><td>Behavior</td><td><span class="badge bg-warning text-dark">B1-B4</span></td><td class="text-muted">Looking / leaving seat — needs review</td></tr><tr><td>Review</td><td><span class="badge bg-warning text-dark">Pending</span></td><td class="text-muted">Awaiting human decision</td></tr><tr><td>Job</td><td><span class="badge bg-primary">Processing</span></td><td class="text-muted">Frames being analyzed</td></tr></tbody></table></div><div class="p-3" style="background:#F8FAFC;border-top:1px solid var(--border);font-size:12px;color:var(--muted)"><i class="bi bi-info-circle me-1"></i> Never color alone — every badge pairs color, text and icon per accessibility.</div></div></div>
</div>
</div>
</section>
<section id="research" class="py-5">
<div class="container" style="max-width:1200px">
<div class="text-center mb-4"><div class="section-label mb-2"><i class="bi bi-mortarboard me-1"></i> Research Contributions</div><h2 style="font-weight:700;letter-spacing:-.02em">Contribution to examination integrity</h2></div>
<div class="row g-3">
<div class="col-md-6 col-lg-4"><div class="card p-3 h-100"><div class="d-flex gap-2 mb-2"><span class="badge bg-primary">01</span><span style="font-size:13px;font-weight:600">AI-assisted surveillance framework</span></div><p class="text-muted mb-0" style="font-size:13px">Unified pipeline for recorded and live exam video — detection, tracking, behavioral rules, evidence generation.</p></div></div>
<div class="col-md-6 col-lg-4"><div class="card p-3 h-100"><div class="d-flex gap-2 mb-2"><span class="badge bg-primary">02</span><span style="font-size:13px;font-weight:600">Lightweight deployment</span></div><p class="text-muted mb-0" style="font-size:13px">Measured low-resource profile, YOLO11n, runs on constrained institutional hardware with reproducible evaluation.</p></div></div>
<div class="col-md-6 col-lg-4"><div class="card p-3 h-100"><div class="d-flex gap-2 mb-2"><span class="badge bg-primary">03</span><span style="font-size:13px;font-weight:600">Human-review workflow</span></div><p class="text-muted mb-0" style="font-size:13px">Event → evidence → reviewer decision model with audit trail, role-based access and retention controls.</p></div></div>
<div class="col-md-6 col-lg-6"><div class="card p-3 h-100"><div class="d-flex gap-2 mb-2"><span class="badge bg-primary">04</span><span style="font-size:13px;font-weight:600">Practical event taxonomy</span></div><p class="text-muted mb-0" style="font-size:13px">D1/D2 object events and B1-B4 behavioral events with thresholds evaluated on classroom recordings.</p></div></div>
<div class="col-md-6 col-lg-6"><div class="card p-3 h-100"><div class="d-flex gap-2 mb-2"><span class="badge bg-primary">05</span><span style="font-size:13px;font-weight:600">Responsible AI stance</span></div><p class="text-muted mb-0" style="font-size:13px">No facial recognition, no auto-accusation, explicit AI notice and requirement for authorized human determination.</p></div></div>
</div>
</div>
</section>
<footer class="footer-dark py-4">
<div class="container" style="max-width:1200px">
<div class="row g-4">
<div class="col-md-6"><div class="d-flex align-items-center gap-2 mb-2"><span class="bg-primary rounded d-flex align-items-center justify-content-center" style="width:28px;height:28px"><i class="bi bi-shield-lock text-white" style="font-size:14px"></i></span><strong style="color:#fff">AI Classroom</strong><span class="badge bg-light text-dark border" style="font-size:10px">Research Prototype</span></div><p class="mb-2" style="font-size:12px;color:#94A3B8">Master of Science (MSc) in IT — Jahangirnagar University. Supervisor: Risala Tasin Khan, PhD.</p><p class="mb-0" style="font-size:11px;color:#64748B"><i class="bi bi-shield-exclamation me-1 text-warning"></i> AI alerts require human review — not proof of misconduct. Final decisions remain with authorized reviewers and the institution.</p></div>
<div class="col-md-3"><div style="font-size:11px;letter-spacing:.08em;text-transform:uppercase;color:#CBD5E1;font-weight:700;margin-bottom:8px">Links</div><ul class="list-unstyled mb-0" style="font-size:13px"><li><a href="{{ route('login') }}"><i class="bi bi-box-arrow-in-right me-1"></i> Login</a></li><li><a href="{{ route('register') }}"><i class="bi bi-person-plus me-1"></i> Register</a></li><li><a href="https://laravel.com/docs" target="_blank" rel="noopener"><i class="bi bi-journal-text me-1"></i> Documentation</a></li><li><a href="https://github.com/Md-Moklesar-Rahman-Bappy/AI-Classroom-Cheating-Detection-System" target="_blank" rel="noopener"><i class="bi bi-github me-1"></i> GitHub Repository</a></li></ul></div>
<div class="col-md-3"><div style="font-size:11px;letter-spacing:.08em;text-transform:uppercase;color:#CBD5E1;font-weight:700;margin-bottom:8px">Contact</div><p class="mb-1" style="font-size:13px"><i class="bi bi-envelope me-1"></i> md.moklasarrahmanbappy@gmail.com</p><p class="mb-0" style="font-size:11px;color:#64748B">© 2026 AI Classroom Cheating Detection System. Licensed after dependency review.</p></div>
</div>
</div>
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
