<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config("app.name") }} - @yield("title")</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root { --sidebar-w: 260px; --sidebar-bg:#0f172a; --sidebar-muted:#94a3b8; --sidebar-active:#1e293b; --card-radius:12px; --card-shadow:0 1px 3px rgba(15,23,42,0.08); --bg:#f8fafc; }
        body { background: var(--bg); font-family: Inter, system-ui, -apple-system, sans-serif; }
        .sidebar { position: fixed; top:0; left:0; width: var(--sidebar-w); height:100vh; background: var(--sidebar-bg); color:#e2e8f0; display:flex; flex-direction:column; z-index:1040; overflow-y:auto; }
        .sidebar-brand { padding:20px 20px 16px; border-bottom:1px solid #1e293b; }
        .sidebar-brand .logo { font-weight:700; font-size:16px; color:#fff; letter-spacing:-0.02em; }
        .sidebar-brand .sub { font-size:11px; color:var(--sidebar-muted); letter-spacing:0.06em; text-transform:uppercase; }
        .sidebar-search { padding:12px 16px; }
        .sidebar-search input { background:#1e293b; border:1px solid #334155; color:#e2e8f0; font-size:13px; }
        .sidebar-search input::placeholder { color:#64748b; }
        .sidebar-nav { flex:1; padding:8px 12px 12px; }
        .nav-section { margin:16px 0 8px; padding:0 8px; font-size:11px; font-weight:600; letter-spacing:0.08em; text-transform:uppercase; color:var(--sidebar-muted); }
        .nav-link { display:flex; align-items:center; gap:10px; padding:8px 10px; border-radius:8px; color:#cbd5e1; font-size:14px; text-decoration:none; }
        .nav-link:hover { background:var(--sidebar-active); color:#fff; }
        .nav-link.active { background:var(--sidebar-active); color:#fff; border-left:3px solid #0d6efd; }
        .nav-link i { font-size:16px; width:16px; }
        .sidebar-footer { padding:12px 16px; border-top:1px solid #1e293b; }
        .main { margin-left: var(--sidebar-w); min-height:100vh; display:flex; flex-direction:column; }
        .topbar { background:#fff; border-bottom:1px solid #e2e8f0; padding:12px 24px; display:flex; align-items:center; justify-content:space-between; gap:12px; }
        .content { padding:24px; flex:1; max-width:1400px; width:100%; margin:0 auto; }
        .ai-notice { background:#fffbeb; border-left:4px solid #f59e0b; padding:12px 16px; border-radius:8px; margin-bottom:16px; font-size:13px; }
        .card { border:1px solid #e2e8f0; border-radius:var(--card-radius); box-shadow:var(--card-shadow); }
        .kpi-card .icon { width:36px; height:36px; border-radius:8px; display:flex; align-items:center; justify-content:center; font-size:16px; }
        .status-badge { font-size:11px; letter-spacing:0.02em; }
        @media (max-width: 991.98px){ .sidebar { transform: translateX(-100%); transition: transform 0.2s; } .sidebar.show { transform: translateX(0); } .main { margin-left:0; } .backdrop { position:fixed; inset:0; background:rgba(0,0,0,0.4); z-index:1039; } }
        .table thead th { font-size:11px; letter-spacing:0.06em; text-transform:uppercase; color:#64748b; font-weight:600; border-bottom:1px solid #e2e8f0; }
        .btn:focus, .form-control:focus { box-shadow:0 0 0 3px rgba(13,110,253,0.15); }
    </style>
    @stack("styles")
</head>
<body>
    <div id="backdrop" class="backdrop d-none" onclick="toggleSidebar()"></div>
    <nav class="sidebar" id="sidebar">
        <div class="sidebar-brand">
            <div class="d-flex align-items-center gap-2">
                <span class="bg-primary rounded d-flex align-items-center justify-content-center" style="width:28px;height:28px;"><i class="bi bi-shield-lock text-white" style="font-size:14px;"></i></span>
                <div><div class="logo">AI Classroom</div><div class="sub">Surveillance Platform</div></div>
            </div>
        </div>
        <div class="sidebar-search">
            <div class="input-group input-group-sm">
                <span class="input-group-text" style="background:#1e293b;border-color:#334155;color:#64748b;"><i class="bi bi-search"></i></span>
                <input type="text" class="form-control" placeholder="Search..." disabled>
            </div>
        </div>
        <div class="sidebar-nav">
            <div class="nav-section">Overview</div>
            <a class="nav-link {{ request()->routeIs("dashboard") ? "active" : "" }}" href="{{ route("dashboard") }}"><i class="bi bi-speedometer2"></i> Dashboard</a>

            <div class="nav-section">Monitoring</div>
            <a class="nav-link {{ request()->routeIs("exam-rooms.*") ? "active" : "" }}" href="{{ route("exam-rooms.index") }}"><i class="bi bi-building"></i> Exam Rooms</a>
            <a class="nav-link {{ request()->routeIs("exam-sessions.*") ? "active" : "" }}" href="{{ route("exam-sessions.index") }}"><i class="bi bi-calendar3"></i> Sessions</a>
            <a class="nav-link {{ request()->routeIs("camera-sources.*") ? "active" : "" }}" href="{{ route("camera-sources.index") }}"><i class="bi bi-camera-video"></i> Cameras</a>
            <a class="nav-link {{ request()->routeIs("video-assets.*") ? "active" : "" }}" href="{{ route("video-assets.index") }}"><i class="bi bi-collection-play"></i> Videos</a>
            <a class="nav-link {{ request()->routeIs("analysis-jobs.*") ? "active" : "" }}" href="{{ route("analysis-jobs.index") }}"><i class="bi bi-cpu"></i> Analysis Jobs</a>

            <div class="nav-section">Detection</div>
            <a class="nav-link {{ request()->routeIs("detection-events.*") ? "active" : "" }}" href="{{ route("detection-events.index") }}"><i class="bi bi-activity"></i> Events</a>
            <a class="nav-link {{ request()->routeIs("evidence.*") ? "active" : "" }}" href="{{ route("detection-events.index") }}"><i class="bi bi-file-earmark-bar-graph"></i> Evidence</a>
            <a class="nav-link" href="{{ route("detection-events.index") }}?review_status=pending"><i class="bi bi-eye"></i> Reviews</a>

            <div class="nav-section">System</div>
            <a class="nav-link {{ request()->routeIs("model-versions.*") ? "active" : "" }}" href="{{ route("model-versions.index") }}"><i class="bi bi-box-seam"></i> Models</a>
            <a class="nav-link {{ request()->routeIs("metrics.*") ? "active" : "" }}" href="{{ route("metrics.index") }}"><i class="bi bi-graph-up"></i> Metrics</a>
            <a class="nav-link {{ request()->routeIs("audit-logs.*") ? "active" : "" }}" href="{{ route("audit-logs.index") }}"><i class="bi bi-journal-text"></i> Audit Logs</a>
            <a class="nav-link {{ request()->routeIs("users.*") ? "active" : "" }}" href="{{ route("users.index") }}"><i class="bi bi-people"></i> Users & Roles</a>
            <a class="nav-link {{ request()->routeIs("settings.*") ? "active" : "" }}" href="{{ route("settings.index") }}"><i class="bi bi-gear"></i> Settings</a>
            <a class="nav-link {{ request()->routeIs("help.*") ? "active" : "" }}" href="{{ route("help.index") }}"><i class="bi bi-question-circle"></i> Help</a>
        </div>
        <div class="sidebar-footer">
            <div class="d-flex align-items-center gap-2">
                <div class="bg-secondary rounded-circle d-flex align-items-center justify-content-center text-white" style="width:32px;height:32px;font-size:12px;">{{ strtoupper(substr(Auth::user()->name ?? "U",0,1)) }}</div>
                <div class="flex-grow-1" style="min-width:0;"><div class="text-white" style="font-size:13px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ Auth::user()->name ?? "User" }}</div><div style="font-size:11px;color:var(--sidebar-muted);">{{ Auth::user()->roles->first()->name ?? "—" }}</div></div>
                <div class="dropdown">
                    <a class="text-muted" href="#" data-bs-toggle="dropdown"><i class="bi bi-three-dots-vertical"></i></a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="{{ route("profile.edit") }}"><i class="bi bi-person me-2"></i>Profile</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><form method="POST" action="{{ route("logout") }}">@csrf<button class="dropdown-item"><i class="bi bi-box-arrow-right me-2"></i>Logout</button></form></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>
    <div class="main">
        <div class="topbar">
            <div class="d-flex align-items-center gap-2">
                <button class="btn btn-outline-secondary d-lg-none" onclick="toggleSidebar()"><i class="bi bi-list"></i></button>
                <nav aria-label="breadcrumb"><ol class="breadcrumb mb-0" style="font-size:13px;"><li class="breadcrumb-item"><a href="{{ route("dashboard") }}" class="text-decoration-none">Home</a></li><li class="breadcrumb-item active">@yield("title")</li></ol></nav>
            </div>
            <div class="d-flex align-items-center gap-3">
                <span class="badge bg-success d-none d-md-inline"><i class="bi bi-circle-fill me-1" style="font-size:8px;"></i> System Operational</span>
            </div>
        </div>
        <div class="content">
            @if(session("success")) <div class="alert alert-success py-2" style="font-size:13px;">{{ session("success") }}</div> @endif
            @if($errors->any()) <div class="alert alert-danger py-2"><ul class="mb-0" style="font-size:13px;">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div> @endif
            <div class="ai-notice"><i class="bi bi-shield-exclamation me-2 text-warning"></i><strong>AI Notice:</strong> AI-generated alerts indicate observable events that require human review. An alert is not proof of academic misconduct.</div>
            @yield("content")
        </div>
        <footer class="border-top bg-white py-3" style="padding-left:24px;padding-right:24px;">
            <div class="d-flex justify-content-between align-items-center" style="font-size:12px;color:#64748b;">
                <span>AI Classroom Cheating Detection — v{{ config("app.version","1.0") }} — SOC Monitoring</span>
                <a href="{{ route("help.index") }}" class="text-decoration-none">Help & Project Notice</a>
            </div>
        </footer>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleSidebar(){
            const sb=document.getElementById("sidebar"), bd=document.getElementById("backdrop");
            sb.classList.toggle("show"); bd.classList.toggle("d-none");
        }
    </script>
    @stack("scripts")
</body>
</html>
