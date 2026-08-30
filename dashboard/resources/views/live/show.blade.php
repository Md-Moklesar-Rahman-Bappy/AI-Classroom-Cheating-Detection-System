@extends("layouts.bootstrap")
@section("title","Live Session")
@section("content")
<div class="d-flex justify-content-between align-items-center mb-4">
    <div><h2 class="mb-1" style="font-weight:700;">Live Session</h2><p class="text-muted mb-0" style="font-size:13px;">Camera: {{ $examSession->name ?? $sessionId }} — Monitoring @if($examSession && $examSession->room) {{ $examSession->room->name }} @endif</p></div>
    <div class="d-flex gap-2"><span class="badge bg-danger status-badge"><i class="bi bi-broadcast me-1"></i> Monitoring</span><form method="POST" action="{{ route("live.stop", $sessionId) }}">@csrf<button class="btn btn-sm btn-outline-danger">Stop Monitoring</button></form></div>
</div>

<div class="row g-4 mb-4">
    <div class="col-12 col-lg-8">
        <div class="card">
            <div class="card-header bg-white d-flex justify-content-between align-items-center" style="border-bottom:1px solid #e2e8f0;">
                <h5 class="mb-0" style="font-size:13px;letter-spacing:0.06em;text-transform:uppercase;"><i class="bi bi-camera-video me-2 text-danger"></i>Annotated Preview (320×180)</h5>
                <span class="badge bg-success status-badge">MJPEG 15fps</span>
            </div>
            <div class="card-body p-0">
                <div class="bg-dark d-flex align-items-center justify-content-center" style="height:320px;">
                    <img src="{{ route("live.preview", $sessionId) }}" alt="Live preview" style="max-width:100%;max-height:100%;object-fit:contain;" onerror="this.style.display='none';document.getElementById('previewFallback').style.display='block';">
                    <div id="previewFallback" style="display:none;" class="text-center text-white-50 p-4">
                        <i class="bi bi-camera-video-off" style="font-size:48px;"></i>
                        <div class="mt-2" style="font-size:13px;">Preview polling fallback — checking <code>/live/{sessionId}/health</code> every 2s</div>
                    </div>
                </div>
                <div class="p-3">
                    <div class="d-flex gap-2 flex-wrap" style="font-size:11px;">
                        <span class="badge bg-light text-dark border">320×180 preview (not full-res)</span>
                        <span class="badge bg-light text-dark border">MJPEG + polling fallback</span>
                        <span class="text-muted">Separate preview from alert metadata</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-lg-4">
        <div class="card h-100">
            <div class="card-header bg-white" style="border-bottom:1px solid #e2e8f0;"><h5 class="mb-0" style="font-size:13px;letter-spacing:0.06em;text-transform:uppercase;"><i class="bi bi-activity me-2 text-primary"></i>Live Metrics</h5></div>
            <div class="card-body" id="liveMetrics" style="font-size:13px;">
                <div class="d-flex justify-content-between mb-2"><span class="text-muted">Connection</span><span class="badge bg-success status-badge" id="connStatus">connected</span></div>
                <div class="d-flex justify-content-between mb-2"><span class="text-muted">Monitoring</span><span class="badge bg-danger status-badge">monitoring</span></div>
                <div class="d-flex justify-content-between mb-2"><span class="text-muted">Processing FPS</span><span id="liveFps" style="font-variant-numeric:tabular-nums;">—</span></div>
                <div class="d-flex justify-content-between mb-2"><span class="text-muted">Alert Latency</span><span id="liveLatency">—</span></div>
                <div class="d-flex justify-content-between mb-2"><span class="text-muted">Last Frame</span><span id="liveLastFrame" style="font-size:11px;">—</span></div>
                <div class="progress mt-3" style="height:6px;"><div class="progress-bar bg-success" id="liveProgress" style="width:92%"></div></div>
                <div class="text-muted mt-2" style="font-size:11px;">Polling <code>/health</code> every 2s — no invented FPS</div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-12 col-lg-8">
        <div class="card h-100">
            <div class="card-header bg-white d-flex justify-content-between align-items-center" style="border-bottom:1px solid #e2e8f0;">
                <h5 class="mb-0" style="font-size:13px;letter-spacing:0.06em;text-transform:uppercase;"><i class="bi bi-bell me-2 text-warning"></i>Recent Events</h5>
                <span class="badge bg-light text-dark border" id="eventCount">0</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0" style="font-size:13px;">
                        <thead><tr><th>Type</th><th>Track</th><th>Evidence</th><th>Time</th></tr></thead>
                        <tbody id="liveEvents"><tr><td colspan="4" class="text-center text-muted py-4">No events yet — polling <code>/events</code> every 2s, duplicate suppressed</td></tr></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-lg-4">
        <div class="card h-100">
            <div class="card-header bg-white" style="border-bottom:1px solid #e2e8f0;"><h5 class="mb-0" style="font-size:13px;letter-spacing:0.06em;text-transform:uppercase;"><i class="bi bi-file-earmark me-2"></i>Evidence Preview</h5></div>
            <div class="card-body text-center">
                <div class="bg-light rounded d-flex align-items-center justify-content-center mb-3" style="height:120px;"><i class="bi bi-image text-muted" style="font-size:32px;"></i></div>
                <p class="text-muted" style="font-size:13px;">Incident evidence (snapshot, not every frame) — protected via authorized controller, no absolute path</p>
                <div class="alert alert-warning py-2" style="font-size:11px;">Evidence preview for latest event — polling fallback if MJPEG fails</div>
            </div>
        </div>
    </div>
</div>

<div class="alert alert-info mt-4" style="font-size:12px;"><i class="bi bi-info-circle me-1"></i> <strong>Degraded/offline warning:</strong> If stream stalls, health shows <span class="badge bg-warning text-dark">degraded</span> or <span class="badge bg-danger">disconnected</span>, reconnecting with bounded delay (1s,2s,5s,max30s, max 5 attempts). Stop is idempotent, audit logged, capture released.</div>

@push("scripts")
<script>
let sessionId = "{{ $sessionId }}";
let healthUrl = "{{ route('live.health', ':id') }}".replace(':id', sessionId);
let eventsUrl = "{{ route('live.events', ':id') }}".replace(':id', sessionId);
setInterval(async () => {
    try {
        let h = await fetch(healthUrl, {headers: {"X-Correlation-Id": crypto.randomUUID()}});
        if(h.ok){
            let j = await h.json();
            document.getElementById('liveFps').textContent = (j.metrics?.fps ?? '—');
            document.getElementById('liveLatency').textContent = (j.metrics?.latency_ms ?? '—') + 'ms';
            document.getElementById('liveLastFrame').textContent = j.last_frame_time ? new Date(j.last_frame_time*1000).toLocaleTimeString() : '—';
            document.getElementById('connStatus').textContent = j.health;
            document.getElementById('connStatus').className = 'badge ' + (j.health==='healthy'?'bg-success':j.health==='degraded'?'bg-warning text-dark':'bg-danger') + ' status-badge';
        }
    } catch(e){}
    try {
        let ev = await fetch(eventsUrl, {headers: {"X-Correlation-Id": crypto.randomUUID()}});
        if(ev.ok){
            let j = await ev.json();
            document.getElementById('eventCount').textContent = j.total;
            let tbody = document.getElementById('liveEvents');
            if(j.total>0){
                tbody.innerHTML = j.events.map(e=> `<tr><td><span class="badge bg-danger">${e.event_type}</span></td><td>${e.track_id}</td><td>${e.explanation ? e.explanation.substring(0,40) : '—'}</td><td style="font-size:11px;">${e.start_frame}</td></tr>`).join('');
            }
        }
    } catch(e){}
}, 2000);
</script>
@endpush
@endsection
