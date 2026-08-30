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
        :root {
            --color-primary: #0d6efd;
            --color-success: #198754;
            --color-warning: #ffc107;
            --color-danger: #dc3545;
            --color-info: #0dcaf0;
            --color-gray: #6c757d;
            --radius: 0.5rem;
            --shadow: 0 0.125rem 0.25rem rgba(0,0,0,0.075);
        }
        .status-badge { font-size: 0.85em; }
        .ai-notice { background: #fff3cd; border-left: 4px solid #ffc107; padding: 1rem; margin-bottom: 1rem; }
        .card { border-radius: var(--radius); box-shadow: var(--shadow); }
        .table-responsive { border-radius: var(--radius); }
        .btn:focus, .form-control:focus { box-shadow: 0 0 0 0.2rem rgba(13,110,253,0.25); }
    </style>
    @stack("styles")
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="{{ route("dashboard") }}">AI Classroom</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbars">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbars">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item"><a class="nav-link" href="{{ route("dashboard") }}">Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="{{ route("exam-rooms.index") }}">Rooms</a></li>
                    <li class="nav-item"><a class="nav-link" href="{{ route("exam-sessions.index") }}">Sessions</a></li>
                    <li class="nav-item"><a class="nav-link" href="{{ route("camera-sources.index") }}">Cameras</a></li>
                    <li class="nav-item"><a class="nav-link" href="{{ route("video-assets.index") }}">Videos</a></li>
                    <li class="nav-item"><a class="nav-link" href="{{ route("analysis-jobs.index") }}">Jobs</a></li>
                    <li class="nav-item"><a class="nav-link" href="{{ route("detection-events.index") }}">Events</a></li>
                    <li class="nav-item"><a class="nav-link" href="{{ route("model-versions.index") }}">Models</a></li>
                    <li class="nav-item"><a class="nav-link" href="{{ route("audit-logs.index") }}">Audit</a></li>
                    @can("view", App\Models\User::class) <li class="nav-item"><a class="nav-link" href="{{ route("users.index") }}">Users</a></li> @endcan
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item"><span class="nav-link">AI-generated alerts indicate observable events that require human review. An alert is not proof of academic misconduct.</span></li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">{{ Auth::user()->name }}</a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="{{ route("profile.edit") }}">Profile</a></li>
                            <li><form method="POST" action="{{ route("logout") }}">@csrf<button class="dropdown-item">Logout</button></form></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    <main class="container py-4">
        @if(session("success")) <div class="alert alert-success">{{ session("success") }}</div> @endif
        @if($errors->any()) <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div> @endif
        <div class="ai-notice">
            <strong>AI Notice:</strong> AI-generated alerts indicate observable events that require human review. An alert is not proof of academic misconduct.
        </div>
        @yield("content")
    </main>
    <footer class="bg-white border-top py-3 mt-4">
        <div class="container text-center text-muted small">
            AI Classroom Cheating Detection System &mdash; v{{ config("app.version","1.0") }} &mdash; <a href="{{ route("help.index") }}">Help</a>
        </div>
    </footer>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    @stack("scripts")
</body>
</html>
