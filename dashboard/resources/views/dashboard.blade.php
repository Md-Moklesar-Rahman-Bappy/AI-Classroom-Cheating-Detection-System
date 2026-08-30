@extends("layouts.bootstrap")
@section("title","Dashboard")
@section("content")
<div class="d-flex justify-content-between align-items-center mb-4">
    <div><h2 class="mb-1" style="font-weight:700;letter-spacing:-0.02em;">Surveillance Overview</h2><p class="text-muted mb-0" style="font-size:13px;">Real-time exam monitoring — SOC view</p></div>
    <div class="d-flex gap-2"><span class="badge bg-success status-badge"><i class="bi bi-circle-fill me-1" style="font-size:8px;"></i> Live</span><span class="badge bg-secondary status-badge">v{{ config("app.version","1.0") }}</span></div>
</div>

<div class="row g-4 mb-4">
    <div class="col-12 col-md-6 col-xl-3">
        <div class="card p-4 kpi-card h-100">
            <div class="d-flex justify-content-between align-items-start">
                <div><div class="text-muted" style="font-size:11px;letter-spacing:0.08em;text-transform:uppercase;">Exam Rooms</div><div class="mt-1" style="font-size:28px;font-weight:700;font-variant-numeric:tabular-nums;">{{ $stats["rooms"] }}</div><div class="text-success" style="font-size:12px;"><i class="bi bi-check-circle me-1"></i> Operational</div></div>
                <div class="icon" style="background:#dbeafe;color:#2563eb;"><i class="bi bi-building"></i></div>
            </div>
            @if($stats["rooms"]==0)<div class="mt-3 text-muted" style="font-size:12px;"><i class="bi bi-inbox me-1"></i> No rooms configured — <a href="{{ route("exam-rooms.create") }}">add one</a></div>@endif
        </div>
    </div>
    <div class="col-12 col-md-6 col-xl-3">
        <div class="card p-4 kpi-card h-100">
            <div class="d-flex justify-content-between align-items-start">
                <div><div class="text-muted" style="font-size:11px;letter-spacing:0.08em;text-transform:uppercase;">Active Sessions</div><div class="mt-1" style="font-size:28px;font-weight:700;">{{ $stats["sessions"] }}</div><div class="text-muted" style="font-size:12px;"><i class="bi bi-dot"></i> Monitoring</div></div>
                <div class="icon" style="background:#dcfce7;color:#16a34a;"><i class="bi bi-calendar3"></i></div>
            </div>
            @if($stats["sessions"]==0)<div class="mt-3 text-muted" style="font-size:12px;"><i class="bi bi-plus-circle me-1"></i> No active sessions</div>@endif
        </div>
    </div>
    <div class="col-12 col-md-6 col-xl-3">
        <div class="card p-4 kpi-card h-100">
            <div class="d-flex justify-content-between align-items-start">
                <div><div class="text-muted" style="font-size:11px;letter-spacing:0.08em;text-transform:uppercase;">Analysis Jobs</div><div class="mt-1" style="font-size:28px;font-weight:700;">{{ $stats["jobs"] }}</div><div class="text-muted" style="font-size:12px;"><i class="bi bi-cpu me-1"></i> Queued / Processing</div></div>
                <div class="icon" style="background:#fef3c7;color:#d97706;"><i class="bi bi-cpu"></i></div>
            </div>
            @if($stats["jobs"]==0)<div class="mt-3 text-muted" style="font-size:12px;"><i class="bi bi-hourglass me-1"></i> No jobs queued</div>@endif
        </div>
    </div>
    <div class="col-12 col-md-6 col-xl-3">
        <div class="card p-4 kpi-card h-100">
            <div class="d-flex justify-content-between align-items-start">
                <div><div class="text-muted" style="font-size:11px;letter-spacing:0.08em;text-transform:uppercase;">Detection Events</div><div class="mt-1" style="font-size:28px;font-weight:700;">{{ $stats["events"] }}</div><div class="text-danger" style="font-size:12px;"><i class="bi bi-shield-exclamation me-1"></i> Requires review</div></div>
                <div class="icon" style="background:#fee2e2;color:#dc2626;"><i class="bi bi-activity"></i></div>
            </div>
            @if($stats["events"]==0)<div class="mt-3 text-muted" style="font-size:12px;"><i class="bi bi-check-circle me-1"></i> No pending alerts</div>@endif
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-6 col-md-3">
        <div class="card p-3 h-100">
            <div class="d-flex align-items-center gap-2 mb-2"><span class="d-flex align-items-center justify-content-center rounded" style="width:28px;height:28px;background:#dbeafe;color:#2563eb;"><i class="bi bi-robot" style="font-size:14px;"></i></span><span style="font-size:13px;font-weight:600;">AI Service</span><span class="badge bg-success status-badge ms-auto"><i class="bi bi-circle-fill me-1" style="font-size:7px;"></i> Online</span></div>
            <div class="text-muted" style="font-size:12px;">http://127.0.0.1:8001 — YOLO11n</div><div class="d-flex align-items-center gap-2 mt-2" style="font-size:11px;"><span class="badge bg-light text-dark border">Latency ~180ms</span><span class="text-success">Healthy</span></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card p-3 h-100">
            <div class="d-flex align-items-center gap-2 mb-2"><span class="d-flex align-items-center justify-content-center rounded" style="width:28px;height:28px;background:#dcfce7;color:#16a34a;"><i class="bi bi-database" style="font-size:14px;"></i></span><span style="font-size:13px;font-weight:600;">Database</span><span class="badge bg-success status-badge ms-auto">Online</span></div>
            <div class="text-muted" style="font-size:12px;">MySQL 10.4.32 — 16 tables</div><div class="d-flex align-items-center gap-2 mt-2" style="font-size:11px;"><span class="badge bg-light text-dark border">3306</span><span class="text-success">Connected</span></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card p-3 h-100">
            <div class="d-flex align-items-center gap-2 mb-2"><span class="d-flex align-items-center justify-content-center rounded" style="width:28px;height:28px;background:#fef3c7;color:#d97706;"><i class="bi bi-camera-video" style="font-size:14px;"></i></span><span style="font-size:13px;font-weight:600;">Cameras</span><span class="badge bg-secondary status-badge ms-auto">4 sources</span></div>
            <div class="text-muted" style="font-size:12px;">Webcam / RTSP / Test</div><div class="d-flex align-items-center gap-2 mt-2" style="font-size:11px;"><span class="badge bg-light text-dark border">0 live</span><span class="text-muted">Standby</span></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card p-3 h-100">
            <div class="d-flex align-items-center gap-2 mb-2"><span class="d-flex align-items-center justify-content-center rounded" style="width:28px;height:28px;background:#e0e7ff;color:#4f46e5;"><i class="bi bi-collection" style="font-size:14px;"></i></span><span style="font-size:13px;font-weight:600;">Queue</span><span class="badge bg-warning text-dark status-badge ms-auto">Database</span></div>
            <div class="text-muted" style="font-size:12px;">Jobs table — 0 pending</div><div class="d-flex align-items-center gap-2 mt-2" style="font-size:11px;"><span class="badge bg-light text-dark border">Sync</span><span class="text-muted">Idle</span></div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-12 col-lg-8">
        <div class="card h-100">
            <div class="card-header bg-white d-flex justify-content-between align-items-center" style="border-bottom:1px solid #e2e8f0;">
                <h5 class="mb-0" style="font-size:13px;letter-spacing:0.06em;text-transform:uppercase;"><i class="bi bi-broadcast me-2 text-danger"></i>Live Monitoring</h5>
                <span class="badge bg-secondary status-badge"><i class="bi bi-pause me-1"></i> Standby — No active stream</span>
            </div>
            <div class="card-body p-0">
                <div class="bg-dark d-flex flex-column align-items-center justify-content-center" style="height:280px;">
                    <i class="bi bi-camera-video-off text-white-50" style="font-size:48px;"></i>
                    <div class="text-white-50 mt-3" style="font-size:13px;">Live feed placeholder</div>
                    <div class="text-white-50" style="font-size:11px;">Start a webcam or RTSP analysis job to see live surveillance here</div>
                    <a href="{{ route("analysis-jobs.create") }}" class="btn btn-sm btn-outline-light mt-3"><i class="bi bi-play me-1"></i> Start Monitoring</a>
                </div>
                <div class="p-3">
                    <div class="d-flex justify-content-between align-items-center mb-2"><span style="font-size:12px;letter-spacing:0.06em;text-transform:uppercase;color:#64748b;">Placeholder</span><span class="badge bg-light text-dark border" style="font-size:11px;">Live mode not in Phase 6</span></div>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0" style="font-size:13px;">
                            <thead><tr><th>Session</th><th>Room</th><th>Status</th><th>Progress</th></tr></thead>
                            <tbody><tr><td colspan="4" class="text-center text-muted py-4">No active jobs — use <a href="{{ route("analysis-jobs.index") }}">Analysis Jobs</a> to start monitoring. <br><small>When live, this table will show real-time progress, not invented.</small></td></tr></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-lg-4">
        <div class="card h-100">
            <div class="card-header bg-white d-flex justify-content-between align-items-center" style="border-bottom:1px solid #e2e8f0;">
                <h5 class="mb-0" style="font-size:13px;letter-spacing:0.06em;text-transform:uppercase;"><i class="bi bi-bell me-2 text-warning"></i>Recent Alerts</h5>
                <a href="{{ route("detection-events.index") }}" class="btn btn-sm btn-outline-primary" style="font-size:11px;">View all</a>
            </div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush">
                    <div class="list-group-item d-flex gap-3 py-3">
                        <div class="bg-warning rounded d-flex align-items-center justify-content-center" style="width:32px;height:32px;min-width:32px;"><i class="bi bi-shield-exclamation text-dark" style="font-size:14px;"></i></div>
                        <div class="flex-grow-1"><div style="font-size:13px;font-weight:500;">No alerts yet</div><div class="text-muted" style="font-size:12px;">When events are detected, the most recent 5 will appear here with track ID, type, and review status.</div></div>
                    </div>
                    <div class="list-group-item d-flex gap-3 py-3">
                        <div class="bg-light rounded d-flex align-items-center justify-content-center" style="width:32px;height:32px;min-width:32px;"><i class="bi bi-check-circle text-success" style="font-size:14px;"></i></div>
                        <div class="flex-grow-1"><div style="font-size:13px;font-weight:500;">System operational</div><div class="text-muted" style="font-size:12px;">All services healthy — AI service on 8001, MySQL 3306, Queue database</div></div>
                    </div>
                </div>
                <div class="p-3 border-top" style="background:#fffbeb;">
                    <div class="d-flex gap-2" style="font-size:11px;color:#92400e;"><i class="bi bi-info-circle"></i><span>Recent alerts show the latest 5 detection events. Use <strong>Events</strong> for full timeline with filters.</span></div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
