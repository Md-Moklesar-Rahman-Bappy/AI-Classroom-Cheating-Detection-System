<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config("app.name", "AI Classroom") }} — @yield("title","Dashboard")</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <style>
        :root{
            --color-primary:#2563EB;--color-primary-dark:#1D4ED8;--color-primary-soft:#DBEAFE;
            --color-secondary:#7C3AED;--color-secondary-soft:#EDE9FE;
            --color-teal:#0F766E;--color-teal-soft:#CCFBF1;
            --color-success:#16A34A;--color-success-soft:#DCFCE7;
            --color-warning:#D97706;--color-warning-soft:#FEF3C7;
            --color-danger:#DC2626;--color-danger-soft:#FEE2E2;
            --color-info:#0284C7;--color-info-soft:#E0F2FE;
            --color-sidebar:#0F172A;--color-sidebar-elevated:#172033;--color-sidebar-text:#CBD5E1;--color-sidebar-muted:#94A3B8;--color-sidebar-active:#FFFFFF;
            --color-background:#F5F7FB;--color-surface:#FFFFFF;--color-surface-muted:#F8FAFC;--color-border:#E2E8F0;
            --color-text:#172033;--color-text-muted:#64748B;--color-text-subtle:#94A3B8;
            --radius-sm:8px;--radius-md:12px;--radius-lg:16px;
            --shadow-sm:0 1px 2px rgba(15,23,42,.06);--shadow-md:0 4px 12px rgba(15,23,42,.08);
            --sidebar-w:272px;--sidebar-collapsed:72px;--topbar-h:56px;
        }
        *{font-family:Inter,system-ui,-apple-system,Segoe UI,sans-serif}
        body{background:var(--color-background);color:var(--color-text);margin:0}
        .skip-link{position:absolute;top:-40px;left:8px;background:var(--color-primary);color:#fff;padding:8px 12px;border-radius:var(--radius-sm);z-index:9999;text-decoration:none;font-size:13px}
        .skip-link:focus{top:8px}
        .sidebar{position:fixed;top:0;left:0;width:var(--sidebar-w);height:100vh;background:var(--color-sidebar);color:var(--color-sidebar-text);display:flex;flex-direction:column;z-index:1040;overflow-y:auto;overflow-x:hidden;transition:width .2s,transform .2s;border-right:1px solid #1e293b}
        .sidebar-brand{padding:20px 16px 14px;border-bottom:1px solid #1e293b;display:flex;align-items:center;justify-content:space-between;gap:8px}
        .sidebar-brand .logo{font-weight:700;font-size:15px;color:#fff;letter-spacing:-.02em;line-height:1}
        .sidebar-brand .sub{font-size:10px;color:var(--color-sidebar-muted);letter-spacing:.08em;text-transform:uppercase;margin-top:2px}
        .sidebar-close{border:none;background:rgba(255,255,255,.08);color:var(--color-sidebar-muted);width:32px;height:32px;border-radius:var(--radius-sm);display:none;align-items:center;justify-content:center}
        .sidebar-nav{flex:1;padding:10px 10px 12px}
        .nav-section{margin:18px 0 6px;padding:0 8px;font-size:10px;font-weight:600;letter-spacing:.08em;text-transform:uppercase;color:var(--color-sidebar-muted)}
        .nav-link{display:flex;align-items:center;gap:10px;padding:8px 10px;border-radius:var(--radius-sm);color:var(--color-sidebar-text);font-size:13px;font-weight:500;text-decoration:none;transition:all .15s;border-left:3px solid transparent}
        .nav-link:hover{background:var(--color-sidebar-elevated);color:#fff}
        .nav-link.active{background:var(--color-sidebar-elevated);color:var(--color-sidebar-active);border-left-color:var(--color-primary)}
        .nav-link i{font-size:15px;width:16px;text-align:center;flex-shrink:0}
        .nav-link .badge{margin-left:auto;font-size:10px}
        .sidebar-footer{padding:12px;border-top:1px solid #1e293b;margin-top:auto;background:rgba(255,255,255,.02)}
        .sidebar-footer .user-card{display:flex;align-items:center;gap:10px}
        .sidebar-footer .avatar{width:32px;height:32px;border-radius:50%;background:var(--color-primary);color:#fff;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;flex-shrink:0}
        .sidebar-footer .user-name{font-size:13px;font-weight:600;color:#fff;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;line-height:1}
        .sidebar-footer .user-role{font-size:11px;color:var(--color-sidebar-muted);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
        .sidebar.collapsed{width:var(--sidebar-collapsed)}
        .sidebar.collapsed .nav-section,.sidebar.collapsed .sub,.sidebar.collapsed .nav-link span:not(.badge),.sidebar.collapsed .user-meta,.sidebar.collapsed .sidebar-footer .btn-text{display:none}
        .sidebar.collapsed .nav-link{justify-content:center;padding:10px;border-left-width:0}
        .sidebar.collapsed .sidebar-brand{justify-content:center}
        .sidebar.collapsed + .main{margin-left:var(--sidebar-collapsed)}
        .main{margin-left:var(--sidebar-w);min-height:100vh;display:flex;flex-direction:column;transition:margin-left .2s}
        .topbar{height:var(--topbar-h);background:var(--color-surface);border-bottom:1px solid var(--color-border);padding:0 20px;display:flex;align-items:center;justify-content:space-between;gap:12px;position:sticky;top:0;z-index:1020}
        .content{padding:24px;flex:1;max-width:1400px;width:100%;margin:0 auto}
        .ai-notice{background:#fffbeb;border-left:4px solid var(--color-warning);padding:12px 16px;border-radius:var(--radius-sm);margin-bottom:16px;font-size:13px;color:#92400e;display:flex;gap:10px;align-items:flex-start}
        .ai-notice strong{color:#78350f}
        .card{border:1px solid var(--color-border);border-radius:var(--radius-md);box-shadow:var(--shadow-sm);background:var(--color-surface)}
        .card-header{border-bottom:1px solid var(--color-border)}
        .kpi-card .icon{width:36px;height:36px;border-radius:var(--radius-sm);display:flex;align-items:center;justify-content:center;font-size:16px}
        .status-badge{font-size:11px;letter-spacing:.02em;font-weight:600}
        .table thead th{font-size:11px;letter-spacing:.06em;text-transform:uppercase;color:var(--color-text-muted);font-weight:600;border-bottom:1px solid var(--color-border);white-space:nowrap}
        .btn:focus,.form-control:focus,.form-select:focus{box-shadow:0 0 0 3px rgba(37,99,235,.15);border-color:var(--color-primary)}
        .breadcrumb{font-size:13px;margin:0}
        .backdrop{position:fixed;inset:0;background:rgba(15,23,42,.45);backdrop-filter:blur(2px);z-index:1039}
        .empty-state{text-align:center;padding:40px 24px}
        .empty-state .empty-icon{width:48px;height:48px;background:#f1f5f9;border-radius:var(--radius-md);display:flex;align-items:center;justify-content:center;margin:0 auto 12px;color:var(--color-text-muted);font-size:20px}
        .focus-ring:focus-visible{outline:2px solid var(--color-primary);outline-offset:2px}
        @media(max-width:991.98px){.sidebar{transform:translateX(-100%)}.sidebar.show{transform:translateX(0)}.sidebar-close{display:flex}.main{margin-left:0}.content{padding:16px}}
        @media(prefers-reduced-motion:reduce){*{transition:none!important;animation:none!important}}
        code{font-family:JetBrains Mono,monospace;font-size:12px}
        .text-mono{font-family:JetBrains Mono,monospace}
        .truncate{overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
    </style>
    @stack("styles")
</head>
<body>
    <a href="#main-content" class="skip-link">Skip to content</a>
    <div id="backdrop" class="backdrop d-none" onclick="closeSidebar()" aria-hidden="true"></div>
    <nav class="sidebar" id="sidebar" aria-label="Primary">
        <div class="sidebar-brand">
            <div class="d-flex align-items-center gap-2" style="min-width:0">
                <span class="bg-primary rounded d-flex align-items-center justify-content-center flex-shrink-0" style="width:28px;height:28px"><i class="bi bi-shield-lock text-white" style="font-size:14px" aria-hidden="true"></i></span>
                <div style="min-width:0"><div class="logo">AI Classroom</div><div class="sub">Surveillance Platform</div></div>
            </div>
            <button class="sidebar-close" onclick="closeSidebar()" aria-label="Close navigation"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
        </div>
        <div class="sidebar-nav" id="sidebarNav">
            <div class="nav-section">Overview</div>
            <a class="nav-link {{ request()->routeIs("dashboard") ? "active" : "" }}" href="{{ route("dashboard") }}" onclick="autoCloseSidebar()"><i class="bi bi-speedometer2" aria-hidden="true"></i> <span>Dashboard</span></a>

            <div class="nav-section">Exam Management</div>
            <a class="nav-link {{ request()->routeIs("exam-rooms.*") ? "active" : "" }}" href="{{ route("exam-rooms.index") }}" onclick="autoCloseSidebar()"><i class="bi bi-building" aria-hidden="true"></i> <span>Rooms</span></a>
            <a class="nav-link {{ request()->routeIs("exam-sessions.*") ? "active" : "" }}" href="{{ route("exam-sessions.index") }}" onclick="autoCloseSidebar()"><i class="bi bi-calendar3" aria-hidden="true"></i> <span>Sessions</span></a>

            <div class="nav-section">Monitoring</div>
            <a class="nav-link {{ request()->routeIs("live.*") ? "active" : "" }}" href="{{ route("live.index") }}" onclick="autoCloseSidebar()"><i class="bi bi-broadcast" aria-hidden="true"></i> <span>Live Monitoring</span></a>
            <a class="nav-link {{ request()->routeIs("camera-sources.*") ? "active" : "" }}" href="{{ route("camera-sources.index") }}" onclick="autoCloseSidebar()"><i class="bi bi-camera-video" aria-hidden="true"></i> <span>Cameras</span></a>
            <a class="nav-link {{ request()->routeIs("video-assets.*") ? "active" : "" }}" href="{{ route("video-assets.index") }}" onclick="autoCloseSidebar()"><i class="bi bi-collection-play" aria-hidden="true"></i> <span>Video Assets</span></a>
            <a class="nav-link {{ request()->routeIs("analysis-jobs.*") ? "active" : "" }}" href="{{ route("analysis-jobs.index") }}" onclick="autoCloseSidebar()"><i class="bi bi-cpu" aria-hidden="true"></i> <span>Analysis Jobs</span></a>

            <div class="nav-section">Detection & Review</div>
            <a class="nav-link {{ request()->routeIs("detection-events.*") ? "active" : "" }}" href="{{ route("detection-events.index") }}" onclick="autoCloseSidebar()"><i class="bi bi-activity" aria-hidden="true"></i> <span>Events</span></a>
            <a class="nav-link {{ request()->routeIs("evidence.*") ? "active" : "" }}" href="{{ route("detection-events.index") }}" onclick="autoCloseSidebar()"><i class="bi bi-file-earmark-bar-graph" aria-hidden="true"></i> <span>Evidence</span></a>
            <a class="nav-link {{ request()->routeIs("detection-events.*") && request()->query("review_status")=="pending" ? "active" : "" }}" href="{{ route("detection-events.index") }}?review_status=pending" onclick="autoCloseSidebar()"><i class="bi bi-eye" aria-hidden="true"></i> <span>Reviews</span> <span class="badge bg-warning text-dark">pending</span></a>
            <a class="nav-link {{ request()->routeIs("reports.*") ? "active" : "" }}" href="{{ route("analysis-jobs.index") }}" onclick="autoCloseSidebar()"><i class="bi bi-file-text" aria-hidden="true"></i> <span>Reports</span></a>

            <div class="nav-section">Analytics</div>
            <a class="nav-link {{ request()->routeIs("metrics.*") ? "active" : "" }}" href="{{ route("metrics.index") }}" onclick="autoCloseSidebar()"><i class="bi bi-graph-up" aria-hidden="true"></i> <span>Metrics</span></a>
            <a class="nav-link {{ request()->routeIs("model-versions.*") ? "active" : "" }}" href="{{ route("model-versions.index") }}" onclick="autoCloseSidebar()"><i class="bi bi-box-seam" aria-hidden="true"></i> <span>Model Versions</span></a>

            <div class="nav-section">Administration</div>
            @can("viewAny", App\Models\User::class)
            <a class="nav-link {{ request()->routeIs("users.*") ? "active" : "" }}" href="{{ route("users.index") }}" onclick="autoCloseSidebar()"><i class="bi bi-people" aria-hidden="true"></i> <span>Users & Roles</span></a>
            @endcan
            <a class="nav-link {{ request()->routeIs("audit-logs.*") ? "active" : "" }}" href="{{ route("audit-logs.index") }}" onclick="autoCloseSidebar()"><i class="bi bi-journal-text" aria-hidden="true"></i> <span>Audit Logs</span></a>
            <a class="nav-link {{ request()->routeIs("settings.*") ? "active" : "" }}" href="{{ route("settings.index") }}" onclick="autoCloseSidebar()"><i class="bi bi-gear" aria-hidden="true"></i> <span>Settings</span></a>
            <a class="nav-link {{ request()->routeIs("help.*") ? "active" : "" }}" href="{{ route("help.index") }}" onclick="autoCloseSidebar()"><i class="bi bi-question-circle" aria-hidden="true"></i> <span>Help</span></a>
        </div>
        <div class="sidebar-footer">
            <div class="user-card mb-2">
                <div class="avatar" aria-hidden="true">{{ strtoupper(substr(Auth::user()->name ?? "U",0,1)) }}</div>
                <div class="user-meta" style="min-width:0;flex:1">
                    <div class="user-name truncate">{{ Auth::user()->name ?? "User" }}</div>
                    <div class="user-role truncate">{{ Auth::user()->roles->first()?->description ?? Auth::user()->roles->first()?->name ?? "No Role Assigned" }}</div>
                </div>
                <div class="dropdown">
                    <a class="btn btn-sm" href="#" data-bs-toggle="dropdown" aria-label="User menu" style="color:var(--color-sidebar-muted)"><i class="bi bi-three-dots-vertical" aria-hidden="true"></i></a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="{{ route("profile.edit") }}"><i class="bi bi-person me-2" aria-hidden="true"></i>Profile</a></li>
                        @can("viewAny", App\Models\User::class)<li><a class="dropdown-item" href="{{ route("users.index") }}"><i class="bi bi-people me-2" aria-hidden="true"></i>Users</a></li>@endcan
                        <li><a class="dropdown-item" href="{{ route("settings.index") }}"><i class="bi bi-gear me-2" aria-hidden="true"></i>Settings</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><form method="POST" action="{{ route("logout") }}">@csrf<button class="dropdown-item"><i class="bi bi-box-arrow-right me-2" aria-hidden="true"></i>Logout</button></form></li>
                    </ul>
                </div>
            </div>
            <form method="POST" action="{{ route("logout") }}" class="d-grid">@csrf<button class="btn btn-sm btn-outline-light w-100 focus-ring" style="font-size:12px"><i class="bi bi-box-arrow-right me-1" aria-hidden="true"></i> <span class="btn-text">Logout</span></button></form>
        </div>
    </nav>
    <div class="main">
        <div class="topbar">
            <div class="d-flex align-items-center gap-2" style="min-width:0">
                <button class="btn btn-outline-secondary d-lg-none focus-ring" onclick="toggleSidebar()" aria-label="Open navigation" aria-expanded="false" aria-controls="sidebar" id="menuBtn"><i class="bi bi-list" aria-hidden="true"></i></button>
                <button class="btn btn-outline-secondary d-none d-lg-inline-flex focus-ring" onclick="toggleCollapse()" aria-label="Collapse sidebar" title="Collapse"><i class="bi bi-layout-sidebar-inset" aria-hidden="true"></i></button>
                <nav aria-label="Breadcrumb" class="d-none d-sm-block" style="min-width:0"><ol class="breadcrumb mb-0"><li class="breadcrumb-item"><a href="{{ route("dashboard") }}" class="text-decoration-none">Home</a></li><li class="breadcrumb-item active text-truncate" aria-current="page">@yield("title")</li></ol></nav>
                <span class="d-sm-none text-truncate" style="font-size:13px;font-weight:600">@yield("title")</span>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-success d-none d-md-inline-flex align-items-center gap-1 status-badge"><i class="bi bi-circle-fill" style="font-size:7px" aria-hidden="true"></i> System Operational</span>
                <a href="{{ route("detection-events.index") }}?review_status=pending" class="btn btn-sm btn-outline-warning d-none d-md-inline-flex focus-ring" title="Pending reviews"><i class="bi bi-eye me-1" aria-hidden="true"></i> Reviews</a>
                <form method="POST" action="{{ route("logout") }}" class="d-inline d-lg-none">@csrf<button class="btn btn-sm btn-outline-danger focus-ring" aria-label="Logout"><i class="bi bi-box-arrow-right" aria-hidden="true"></i></button></form>
            </div>
        </div>
        <main id="main-content" class="content" tabindex="-1">
            @if(session("success"))<div class="alert alert-success py-2 d-flex align-items-center gap-2" role="alert" style="font-size:13px"><i class="bi bi-check-circle" aria-hidden="true"></i> {{ session("success") }}</div>@endif
            @if(session("error"))<div class="alert alert-danger py-2" role="alert" style="font-size:13px">{{ session("error") }}</div>@endif
            @if($errors->any())<div class="alert alert-danger py-2" role="alert"><ul class="mb-0" style="font-size:13px">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif
            <div class="ai-notice" role="note" aria-label="Responsible AI notice"><i class="bi bi-shield-exclamation text-warning" aria-hidden="true" style="font-size:16px;flex-shrink:0"></i><div><strong>AI Notice:</strong> AI-generated alerts indicate observable events that require human review. An alert is not proof of academic misconduct. Final academic or disciplinary decisions remain with authorized human reviewers and the institution.</div></div>
            @yield("content")
        </main>
        <footer class="border-top bg-white" style="padding:14px 20px">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-2" style="font-size:12px;color:var(--color-text-muted)">
                <div class="d-flex align-items-center gap-2 flex-wrap"><span class="d-flex align-items-center gap-1"><i class="bi bi-shield-lock text-primary" aria-hidden="true"></i> AI Classroom — Surveillance Platform</span><span class="d-none d-md-inline">•</span><span>v{{ config("app.version","1.1") }}</span><span class="badge bg-light text-dark border" style="font-size:10px">Research Prototype</span></div>
                <div class="d-flex align-items-center gap-3"><span class="d-none d-lg-inline">AI alerts require human review — not proof of misconduct</span><a href="{{ route("help.index") }}" class="text-decoration-none">Help</a><a href="{{ route("audit-logs.index") }}" class="text-decoration-none">Audit</a><span>© 2026</span></div>
            </div>
        </footer>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const sidebar=document.getElementById('sidebar'),backdrop=document.getElementById('backdrop'),menuBtn=document.getElementById('menuBtn');
        function toggleSidebar(){const s=sidebar.classList.toggle('show');backdrop.classList.toggle('d-none',!s);document.body.style.overflow=s?'hidden':'';if(menuBtn)menuBtn.setAttribute('aria-expanded',s);if(s) trapFocus();}
        function closeSidebar(){sidebar.classList.remove('show');backdrop.classList.add('d-none');document.body.style.overflow='';if(menuBtn)menuBtn.setAttribute('aria-expanded','false')}
        function autoCloseSidebar(){if(window.innerWidth<992) closeSidebar()}
        function toggleCollapse(){if(window.innerWidth>=992) sidebar.classList.toggle('collapsed'); else toggleSidebar()}
        document.addEventListener('keydown',e=>{if(e.key==='Escape'&&sidebar.classList.contains('show')) closeSidebar()});
        function trapFocus(){const f=sidebar.querySelectorAll('a,button,[tabindex]:not([tabindex="-1"])');if(f.length) f[0].focus()}
        backdrop.addEventListener('click',closeSidebar);
    </script>
    @stack("scripts")
</body>
</html>
