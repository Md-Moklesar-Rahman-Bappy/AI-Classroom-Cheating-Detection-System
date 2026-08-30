@extends("layouts.bootstrap")
@section("title","Dashboard")
@section("content")
<div class="d-flex justify-content-between align-items-center mb-4">
    <div><h2 class="mb-1" style="font-weight:700;letter-spacing:-0.02em;">Surveillance Overview</h2><p class="text-muted mb-0" style="font-size:13px;">Real-time exam monitoring — SOC view</p></div>
    <div class="d-flex gap-2"><span class="badge bg-success status-badge"><i class="bi bi-circle-fill me-1" style="font-size:8px;"></i> Live</span><span class="badge bg-secondary status-badge">v{{ config("app.version","1.0") }}</span></div>
</div>

<div class="row g-4 mb-4">
    <div class="col-12 col-md-6 col-xl-3">
        <div class="card p-4 kpi-card">
            <div class="d-flex justify-content-between align-items-start">
                <div><div class="text-muted" style="font-size:11px;letter-spacing:0.08em;text-transform:uppercase;">Exam Rooms</div><div class="mt-1" style="font-size:28px;font-weight:700;font-variant-numeric:tabular-nums;">{{ $stats["rooms"] }}</div><div class="text-success" style="font-size:12px;"><i class="bi bi-arrow-up-short"></i> Operational</div></div>
                <div class="icon" style="background:#dbeafe;color:#2563eb;"><i class="bi bi-building"></i></div>
            </div>
            <div class="progress mt-3" style="height:4px;"><div class="progress-bar bg-primary" style="width: 100%"></div></div>
        </div>
    </div>
    <div class="col-12 col-md-6 col-xl-3">
        <div class="card p-4 kpi-card">
            <div class="d-flex justify-content-between align-items-start">
                <div><div class="text-muted" style="font-size:11px;letter-spacing:0.08em;text-transform:uppercase;">Active Sessions</div><div class="mt-1" style="font-size:28px;font-weight:700;">{{ $stats["sessions"] }}</div><div class="text-muted" style="font-size:12px;"><i class="bi bi-dot"></i> Monitoring</div></div>
                <div class="icon" style="background:#dcfce7;color:#16a34a;"><i class="bi bi-calendar3"></i></div>
            </div>
            <div class="progress mt-3" style="height:4px;"><div class="progress-bar bg-success" style="width: 85%"></div></div>
        </div>
    </div>
    <div class="col-12 col-md-6 col-xl-3">
        <div class="card p-4 kpi-card">
            <div class="d-flex justify-content-between align-items-start">
                <div><div class="text-muted" style="font-size:11px;letter-spacing:0.08em;text-transform:uppercase;">Analysis Jobs</div><div class="mt-1" style="font-size:28px;font-weight:700;">{{ $stats["jobs"] }}</div><div class="text-muted" style="font-size:12px;"><i class="bi bi-cpu me-1"></i> Queued / Processing</div></div>
                <div class="icon" style="background:#fef3c7;color:#d97706;"><i class="bi bi-cpu"></i></div>
            </div>
            <div class="progress mt-3" style="height:4px;"><div class="progress-bar bg-warning" style="width: 60%"></div></div>
        </div>
    </div>
    <div class="col-12 col-md-6 col-xl-3">
        <div class="card p-4 kpi-card">
            <div class="d-flex justify-content-between align-items-start">
                <div><div class="text-muted" style="font-size:11px;letter-spacing:0.08em;text-transform:uppercase;">Detection Events</div><div class="mt-1" style="font-size:28px;font-weight:700;">{{ $stats["events"] }}</div><div class="text-danger" style="font-size:12px;"><i class="bi bi-shield-exclamation me-1"></i> Requires review</div></div>
                <div class="icon" style="background:#fee2e2;color:#dc2626;"><i class="bi bi-activity"></i></div>
            </div>
            <div class="progress mt-3" style="height:4px;"><div class="progress-bar bg-danger" style="width: 35%"></div></div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-12 col-lg-8">
        <div class="card">
            <div class="card-header bg-white d-flex justify-content-between align-items-center" style="border-bottom:1px solid #e2e8f0;">
                <h5 class="mb-0" style="font-size:14px;font-weight:600;"><i class="bi bi-camera-video me-2 text-primary"></i>Live Surveillance Strip</h5>
                <span class="badge bg-success status-badge"><i class="bi bi-circle-fill me-1" style="font-size:8px;"></i> Operational</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0" style="font-size:13px;">
                        <thead><tr><th>Session</th><th>Room</th><th>Status</th><th>Progress</th></tr></thead>
                        <tbody>
                            <tr><td>—</td><td>—</td><td><span class="badge bg-success status-badge">Normal</span> Normal</td><td><div class="progress" style="height:6px;width:80px;"><div class="progress-bar bg-success" style="width:100%"></div></div></td></tr>
                            <tr><td colspan="4" class="text-center text-muted py-4">No active jobs — use <a href="{{ route("analysis-jobs.index") }}">Analysis Jobs</a> to start monitoring.</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-lg-4">
        <div class="card h-100">
            <div class="card-header bg-white" style="border-bottom:1px solid #e2e8f0;"><h5 class="mb-0" style="font-size:14px;font-weight:600;"><i class="bi bi-journal-text me-2 text-muted"></i>Recent Activity</h5></div>
            <div class="card-body">
                <div class="d-flex gap-3 mb-3">
                    <div class="bg-success rounded-circle d-flex align-items-center justify-content-center" style="width:32px;height:32px;min-width:32px;"><i class="bi bi-check-lg text-white" style="font-size:14px;"></i></div>
                    <div><div style="font-size:13px;font-weight:500;">System operational</div><div class="text-muted" style="font-size:12px;">All services healthy — AI service on 8001, MySQL 3306</div></div>
                </div>
                <div class="d-flex gap-3 mb-3">
                    <div class="bg-warning rounded-circle d-flex align-items-center justify-content-center" style="width:32px;height:32px;min-width:32px;"><i class="bi bi-shield me-1 text-dark" style="font-size:14px;"></i></div>
                    <div><div style="font-size:13px;font-weight:500;">Review queue</div><div class="text-muted" style="font-size:12px;">Events require human decision — not proof of misconduct</div></div>
                </div>
                <div class="alert alert-info py-2" style="font-size:12px;"><strong>Statuses use text plus color:</strong> <span class="badge bg-success status-badge">Normal</span> <span class="badge bg-warning text-dark status-badge">Pending</span> <span class="badge bg-danger status-badge">Suspicious</span> <span class="badge bg-secondary status-badge">Insufficient</span></div>
            </div>
        </div>
    </div>
</div>
@endsection
