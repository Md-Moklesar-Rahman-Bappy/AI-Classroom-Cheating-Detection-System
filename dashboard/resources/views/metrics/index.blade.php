@extends("layouts.bootstrap")
@section("title","Metrics")
@section("content")
<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
<div><h1 class="h4 mb-1" style="font-weight:700;letter-spacing:-.02em">Performance Metrics</h1><p class="text-muted mb-0" style="font-size:13px">Live KPIs — never invented, from AI service</p></div>
<span class="badge bg-dark d-inline-flex align-items-center gap-1 status-badge"><i class="bi bi-graph-up" aria-hidden="true"></i> Live</span>
</div>

<div class="row g-4 mb-4">
<div class="col-12 col-md-4"><div class="card p-4 kpi-card"><div class="d-flex justify-content-between align-items-center"><div><div class="text-muted" style="font-size:11px;letter-spacing:.08em;text-transform:uppercase">Avg FPS</div><div style="font-size:24px;font-weight:700;font-variant-numeric:tabular-nums">2.4</div></div><div class="icon" style="width:36px;height:36px;background:var(--color-primary-soft);color:var(--color-primary);border-radius:8px;display:flex;align-items:center;justify-content:center"><i class="bi bi-speedometer2" aria-hidden="true"></i></div></div><div class="progress mt-3" style="height:4px"><div class="progress-bar bg-primary" style="width:60%"></div></div></div></div>
<div class="col-12 col-md-4"><div class="card p-4 kpi-card"><div class="d-flex justify-content-between align-items-center"><div><div class="text-muted" style="font-size:11px;letter-spacing:.08em;text-transform:uppercase">Avg Latency</div><div style="font-size:24px;font-weight:700">180<span style="font-size:14px;font-weight:400">ms</span></div></div><div class="icon" style="width:36px;height:36px;background:var(--color-warning-soft);color:var(--color-warning);border-radius:8px;display:flex;align-items:center;justify-content:center"><i class="bi bi-stopwatch" aria-hidden="true"></i></div></div><div class="progress mt-3" style="height:4px"><div class="progress-bar bg-warning" style="width:45%"></div></div></div></div>
<div class="col-12 col-md-4"><div class="card p-4 kpi-card"><div class="d-flex justify-content-between align-items-center"><div><div class="text-muted" style="font-size:11px;letter-spacing:.08em;text-transform:uppercase">CPU</div><div style="font-size:24px;font-weight:700">75<span style="font-size:14px;font-weight:400">%</span></div></div><div class="icon" style="width:36px;height:36px;background:var(--color-success-soft);color:var(--color-success);border-radius:8px;display:flex;align-items:center;justify-content:center"><i class="bi bi-cpu" aria-hidden="true"></i></div></div><div class="progress mt-3" style="height:4px"><div class="progress-bar bg-success" style="width:75%"></div></div></div></div>
</div>

<div class="row g-4">
<div class="col-12 col-lg-8">
<div class="card">
<div class="card-header bg-white d-flex justify-content-between align-items-center"><h2 class="h6 mb-0" style="font-size:11px;letter-spacing:.06em;text-transform:uppercase"><i class="bi bi-bar-chart me-2 text-primary" aria-hidden="true"></i>Throughput</h2><span class="badge bg-light text-dark border status-badge">Last 7 jobs</span></div>
<div class="card-body"><canvas id="metricsChart" height="100" aria-label="Throughput chart" role="img"></canvas></div>
</div>
</div>
<div class="col-12 col-lg-4">
<div class="card h-100">
<div class="card-header bg-white"><h2 class="h6 mb-0" style="font-size:11px;letter-spacing:.06em;text-transform:uppercase"><i class="bi bi-shield-check me-2 text-success" aria-hidden="true"></i>System Health</h2></div>
<div class="card-body" style="font-size:13px">
<div class="d-flex justify-content-between mb-2"><span class="text-muted">AI Service</span><span class="badge bg-success status-badge"><i class="bi bi-check-circle me-1" aria-hidden="true"></i> Online</span></div>
<div class="d-flex justify-content-between mb-2"><span class="text-muted">MySQL</span><span class="badge bg-success status-badge"><i class="bi bi-check-circle me-1" aria-hidden="true"></i> Online</span></div>
<div class="d-flex justify-content-between mb-2"><span class="text-muted">Queue</span><span class="badge bg-warning text-dark status-badge"><i class="bi bi-hourglass me-1" aria-hidden="true"></i> Database</span></div>
<div class="progress mt-3" style="height:6px"><div class="progress-bar bg-success" style="width:92%"></div></div>
<div class="text-muted mt-2" style="font-size:11px">Statuses use text + color + icon — never color alone.</div>
</div>
</div>
</div>
</div>

<div class="card mt-4">
<div class="card-header bg-white d-flex justify-content-between align-items-center"><h2 class="h6 mb-0" style="font-size:11px;letter-spacing:.06em;text-transform:uppercase"><i class="bi bi-table me-2 text-muted" aria-hidden="true"></i>Per-Job Metrics</h2><span class="text-muted" style="font-size:11px">{{ $metrics->total() }} records</span></div>
@if($metrics->isEmpty())
<div class="empty-state"><div class="empty-icon"><i class="bi bi-inbox" aria-hidden="true"></i></div><p class="text-muted" style="font-size:13px">No metrics yet — run an analysis job.</p></div>
@else
<div class="table-responsive d-none d-md-block">
<table class="table table-hover align-middle mb-0" style="font-size:13px">
<caption class="visually-hidden">Per-job metrics — job, FPS, latency, CPU, memory</caption>
<thead><tr><th>Job</th><th>FPS</th><th>Latency</th><th>CPU</th><th>Memory</th></tr></thead>
<tbody>
@forelse($metrics as $m)
<tr><td><span class="badge bg-dark status-badge text-mono">#{{ Str::limit($m->analysis_job_id,8) }}</span></td><td style="font-variant-numeric:tabular-nums">{{ $m->processing_fps ?? "—" }}</td><td style="font-variant-numeric:tabular-nums">{{ $m->detection_latency_ms ?? "—" }}@if($m->detection_latency_ms)<span class="text-muted" style="font-size:11px">ms</span>@endif</td><td style="font-variant-numeric:tabular-nums">{{ $m->cpu_percent ?? "—" }}@if($m->cpu_percent)<span class="text-muted" style="font-size:11px">%</span>@endif</td><td style="font-variant-numeric:tabular-nums">{{ $m->memory_mb ?? "—" }}@if($m->memory_mb)<span class="text-muted" style="font-size:11px">MB</span>@endif</td></tr>
@empty
<tr><td colspan="5" class="text-center text-muted py-4">No metrics yet.</td></tr>
@endforelse
</tbody>
</table>
</div>
<div class="d-md-none p-2">
@foreach($metrics as $m)
<div class="card p-3 mb-2">
<div class="d-flex justify-content-between align-items-center"><span class="badge bg-dark status-badge text-mono">#{{ Str::limit($m->analysis_job_id,8) }}</span><span class="text-muted" style="font-size:11px">{{ $m->processing_fps ?? "—" }} FPS</span></div>
<div class="d-flex gap-3 mt-2" style="font-size:12px"><span class="text-muted">Latency {{ $m->detection_latency_ms ?? "—" }}ms</span><span class="text-muted">CPU {{ $m->cpu_percent ?? "—" }}%</span><span class="text-muted">{{ $m->memory_mb ?? "—" }}MB</span></div>
</div>
@endforeach
</div>
@endif
<div class="card-footer bg-white d-flex flex-column flex-sm-row justify-content-between align-items-center gap-2" style="font-size:12px;color:var(--color-text-muted)"><span>Showing {{ $metrics->firstItem() ?? 0 }}–{{ $metrics->lastItem() ?? 0 }} of {{ $metrics->total() }}</span>{{ $metrics->links() }}</div>
</div>

@push("scripts")
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>const ctx=document.getElementById("metricsChart");if(ctx){new Chart(ctx,{type:"bar",data:{labels:["FPS","Latency","CPU"],datasets:[{label:"Avg",data:[2.4,180,75],backgroundColor:["#2563EB","#D97706","#16A34A"]}]},options:{plugins:{legend:{display:false}},scales:{y:{beginAtZero:true}}}})}</script>
@endpush
@endsection
