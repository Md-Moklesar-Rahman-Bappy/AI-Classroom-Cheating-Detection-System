@extends("layouts.bootstrap")
@section("title","Metrics")
@section("content")
<div class="d-flex justify-content-between align-items-center mb-4">
    <div><h2 class="mb-1" style="font-weight:700;">Performance Metrics</h2><p class="text-muted mb-0" style="font-size:13px;">AI monitoring — text plus color for every status</p></div>
    <span class="badge bg-dark status-badge"><i class="bi bi-graph-up me-1"></i> Live</span>
</div>

<div class="row g-4 mb-4">
    <div class="col-12 col-md-4"><div class="card p-4"><div class="d-flex justify-content-between align-items-center"><div><div class="text-muted" style="font-size:11px;letter-spacing:0.08em;text-transform:uppercase;">Avg FPS</div><div style="font-size:24px;font-weight:700;font-variant-numeric:tabular-nums;">2.4</div></div><div class="icon d-flex align-items-center justify-content-center" style="width:36px;height:36px;background:#dbeafe;color:#2563eb;border-radius:8px;"><i class="bi bi-speedometer2"></i></div></div><div class="progress mt-3" style="height:4px;"><div class="progress-bar bg-primary" style="width:60%"></div></div></div></div>
    <div class="col-12 col-md-4"><div class="card p-4"><div class="d-flex justify-content-between align-items-center"><div><div class="text-muted" style="font-size:11px;letter-spacing:0.08em;text-transform:uppercase;">Avg Latency</div><div style="font-size:24px;font-weight:700;">180<span style="font-size:14px;font-weight:400;">ms</span></div></div><div class="icon d-flex align-items-center justify-content-center" style="width:36px;height:36px;background:#fef3c7;color:#d97706;border-radius:8px;"><i class="bi bi-stopwatch"></i></div></div><div class="progress mt-3" style="height:4px;"><div class="progress-bar bg-warning" style="width:45%"></div></div></div></div>
    <div class="col-12 col-md-4"><div class="card p-4"><div class="d-flex justify-content-between align-items-center"><div><div class="text-muted" style="font-size:11px;letter-spacing:0.08em;text-transform:uppercase;">CPU</div><div style="font-size:24px;font-weight:700;">75<span style="font-size:14px;font-weight:400;">%</span></div></div><div class="icon d-flex align-items-center justify-content-center" style="width:36px;height:36px;background:#dcfce7;color:#16a34a;border-radius:8px;"><i class="bi bi-cpu"></i></div></div><div class="progress mt-3" style="height:4px;"><div class="progress-bar bg-success" style="width:75%"></div></div></div></div>
</div>

<div class="row g-4">
    <div class="col-12 col-lg-8">
        <div class="card">
            <div class="card-header bg-white d-flex justify-content-between align-items-center" style="border-bottom:1px solid #e2e8f0;">
                <h5 class="mb-0" style="font-size:13px;letter-spacing:0.06em;text-transform:uppercase;"><i class="bi bi-bar-chart me-2 text-primary"></i>Throughput</h5>
                <span class="badge bg-secondary status-badge">Last 7 jobs</span>
            </div>
            <div class="card-body"><canvas id="metricsChart" height="100"></canvas></div>
        </div>
    </div>
    <div class="col-12 col-lg-4">
        <div class="card h-100">
            <div class="card-header bg-white" style="border-bottom:1px solid #e2e8f0;"><h5 class="mb-0" style="font-size:13px;letter-spacing:0.06em;text-transform:uppercase;"><i class="bi bi-shield-check me-2 text-success"></i>System Health</h5></div>
            <div class="card-body">
                <div class="d-flex justify-content-between mb-2" style="font-size:13px;"><span class="text-muted">AI Service</span><span class="badge bg-success status-badge">Online</span></div>
                <div class="d-flex justify-content-between mb-2" style="font-size:13px;"><span class="text-muted">MySQL</span><span class="badge bg-success status-badge">Online</span></div>
                <div class="d-flex justify-content-between mb-2" style="font-size:13px;"><span class="text-muted">Queue</span><span class="badge bg-warning text-dark status-badge">Database</span></div>
                <div class="progress mt-3" style="height:6px;"><div class="progress-bar bg-success" style="width:92%"></div></div>
                <div class="text-muted mt-2" style="font-size:11px;">Statuses use text plus color — never color alone.</div>
            </div>
        </div>
    </div>
</div>

<div class="card mt-4">
    <div class="card-header bg-white d-flex justify-content-between align-items-center" style="border-bottom:1px solid #e2e8f0;">
        <h5 class="mb-0" style="font-size:13px;letter-spacing:0.06em;text-transform:uppercase;"><i class="bi bi-table me-2 text-muted"></i>Per-Job Metrics</h5>
        <span class="text-muted" style="font-size:11px;">{{ $metrics->total() }} records</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0" style="font-size:13px;">
            <thead><tr><th>Job</th><th>FPS</th><th>Latency</th><th>CPU</th><th>Memory</th></tr></thead>
            <tbody>
                @forelse($metrics as $m)
                <tr><td><span class="badge bg-dark status-badge">#{{ $m->analysis_job_id }}</span></td><td style="font-variant-numeric:tabular-nums;">{{ $m->processing_fps ?? "—" }}</td><td>{{ $m->detection_latency_ms ?? "—" }}@if($m->detection_latency_ms)<span class="text-muted" style="font-size:11px;">ms</span>@endif</td><td>{{ $m->cpu_percent ?? "—" }}@if($m->cpu_percent)<span class="text-muted" style="font-size:11px;">%</span>@endif</td><td>{{ $m->memory_mb ?? "—" }}@if($m->memory_mb)<span class="text-muted" style="font-size:11px;">MB</span>@endif</td></tr>
                @empty
                <tr><td colspan="5" class="text-center text-muted py-4">No metrics yet — run an analysis job.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer bg-white d-flex justify-content-between align-items-center" style="font-size:12px;color:#64748b;"><span>Showing {{ $metrics->firstItem() ?? 0 }}–{{ $metrics->lastItem() ?? 0 }} of {{ $metrics->total() }}</span> {{ $metrics->links() }}</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>const ctx=document.getElementById("metricsChart"); if(ctx){ new Chart(ctx,{type:"bar",data:{labels:["FPS","Latency","CPU"],datasets:[{label:"Avg",data:[2.4,180,75],backgroundColor:["#0d6efd","#f59e0b","#16a34a"]}]},options:{plugins:{legend:{display:false}},scales:{y:{beginAtZero:true}}}}); }</script>
@endsection
