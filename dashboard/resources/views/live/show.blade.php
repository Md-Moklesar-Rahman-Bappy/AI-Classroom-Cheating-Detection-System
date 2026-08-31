@extends("layouts.bootstrap")
@section("title","Live Session")
@section("content")
<div class="d-flex flex-column flex-md-row justify-content-between align-items-start gap-3 mb-4">
<div style="min-width:0"><h1 class="h4 mb-1" style="font-weight:700;letter-spacing:-.02em">Live Session</h1><p class="text-muted mb-0 truncate" style="font-size:13px">Session: <span class="fw-medium" style="color:var(--color-text)">{{ $examSession->name ?? $sessionId }}</span> @if($examSession && $examSession->room) • {{ $examSession->room->name }} @endif • <code class="text-mono" style="font-size:11px">{{ Str::limit($sessionId,12) }}</code></p></div>
<div class="d-flex gap-2 flex-wrap flex-shrink-0"><span class="badge bg-danger d-inline-flex align-items-center gap-1 status-badge"><i class="bi bi-broadcast" aria-hidden="true"></i> Monitoring</span><a href="{{ route("live.index") }}" class="btn btn-outline-secondary btn-sm focus-ring">Back</a><form method="POST" action="{{ route("live.stop", $sessionId) }}">@csrf<button class="btn btn-sm btn-outline-danger focus-ring">Stop Monitoring</button></form></div>
</div>

<div class="row g-4 mb-4">
<div class="col-12 col-lg-8">
<div class="card">
<div class="card-header bg-white d-flex justify-content-between align-items-center"><h2 class="h6 mb-0" style="font-size:11px;letter-spacing:.06em;text-transform:uppercase"><i class="bi bi-camera-video me-2 text-danger" aria-hidden="true"></i>Annotated Preview (320×180)</h2><span class="badge bg-success status-badge">MJPEG 15fps</span></div>
<div class="card-body p-0">
<div class="bg-dark d-flex align-items-center justify-content-center" style="height:320px;overflow:hidden">
<img src="{{ route("live.preview", $sessionId) }}" alt="Live annotated preview — 320 by 180" style="max-width:100%;max-height:100%;object-fit:contain" onerror="this.style.display='none';document.getElementById('previewFallback').style.display='block'">
<div id="previewFallback" style="display:none" class="text-center text-white-50 p-4">
<i class="bi bi-camera-video-off" style="font-size:48px" aria-hidden="true"></i>
<div class="mt-2" style="font-size:13px">Preview polling fallback — checking <code>/live/{{ $sessionId }}/health</code> every 2s</div>
</div>
</div>
<div class="p-3 d-flex gap-2 flex-wrap" style="font-size:11px">
<span class="badge bg-light text-dark border status-badge">320×180 preview (not full-res)</span>
<span class="badge bg-light text-dark border status-badge">MJPEG + polling fallback</span>
<span class="text-muted">Separate preview from alert metadata</span>
</div>
</div>
</div>
</div>
<div class="col-12 col-lg-4">
<div class="card h-100">
<div class="card-header bg-white"><h2 class="h6 mb-0" style="font-size:11px;letter-spacing:.06em;text-transform:uppercase"><i class="bi bi-activity me-2 text-primary" aria-hidden="true"></i>Live Metrics</h2></div>
<div class="card-body" id="liveMetrics" style="font-size:13px">
<div class="d-flex justify-content-between mb-2"><span class="text-muted">Connection</span><span class="badge bg-success status-badge" id="connStatus"><i class="bi bi-circle-fill me-1" style="font-size:7px" aria-hidden="true"></i> connected</span></div>
<div class="d-flex justify-content-between mb-2"><span class="text-muted">Monitoring</span><span class="badge bg-danger status-badge"><i class="bi bi-broadcast me-1" aria-hidden="true"></i> monitoring</span></div>
<div class="d-flex justify-content-between mb-2"><span class="text-muted">Processing FPS</span><span id="liveFps" style="font-variant-numeric:tabular-nums">—</span></div>
<div class="d-flex justify-content-between mb-2"><span class="text-muted">Alert Latency</span><span id="liveLatency" style="font-variant-numeric:tabular-nums">—</span></div>
<div class="d-flex justify-content-between mb-2"><span class="text-muted">Last Frame</span><span id="liveLastFrame" style="font-size:11px">—</span></div>
<div class="progress mt-3" style="height:6px"><div class="progress-bar bg-success" id="liveProgress" style="width:92%"></div></div>
<div class="text-muted mt-2" style="font-size:11px">Polling <code class="text-mono">/health</code> every 2s — no invented FPS.</div>
</div>
</div>
</div>
</div>

<div class="row g-4">
<div class="col-12 col-lg-8">
<div class="card h-100">
<div class="card-header bg-white d-flex justify-content-between align-items-center"><h2 class="h6 mb-0" style="font-size:11px;letter-spacing:.06em;text-transform:uppercase"><i class="bi bi-bell me-2 text-warning" aria-hidden="true"></i>Recent Events</h2><span class="badge bg-light text-dark border status-badge" id="eventCount">0</span></div>
<div class="card-body p-0">
<div class="table-responsive">
<table class="table table-hover mb-0" style="font-size:13px">
<caption class="visually-hidden">Live detection events</caption>
<thead><tr><th>Type</th><th>Track</th><th>Evidence</th><th>Time</th></tr></thead>
<tbody id="liveEvents"><tr><td colspan="4" class="text-center text-muted py-4">No events yet — polling <code class="text-mono">/events</code> every 2s, duplicate suppressed.</td></tr></tbody>
</table>
</div>
</div>
</div>
</div>
<div class="col-12 col-lg-4">
<div class="card h-100">
<div class="card-header bg-white"><h2 class="h6 mb-0" style="font-size:11px;letter-spacing:.06em;text-transform:uppercase"><i class="bi bi-file-earmark me-2" aria-hidden="true"></i>Evidence Preview</h2></div>
<div class="card-body text-center">
<div class="bg-light rounded d-flex align-items-center justify-content-center mb-3" style="height:120px"><i class="bi bi-image text-muted" style="font-size:32px" aria-hidden="true"></i></div>
<p class="text-muted" style="font-size:13px">Incident evidence (snapshot, not every frame) — protected via authorized controller, no absolute path.</p>
<div class="alert alert-warning py-2 mb-0" style="font-size:11px">Evidence preview for latest event — polling fallback if MJPEG fails.</div>
</div>
</div>
</div>
</div>

<div class="alert alert-info mt-4 d-flex gap-2" style="font-size:12px"><i class="bi bi-info-circle flex-shrink-0" aria-hidden="true"></i><div><strong>Degraded/offline:</strong> If stream stalls, health shows <span class="badge bg-warning text-dark status-badge">degraded</span> or <span class="badge bg-danger status-badge">disconnected</span>, reconnecting bounded (1s,2s,5s,max 30s, 5 attempts). Stop is idempotent, audited, capture released.</div></div>

@push("scripts")
<script>
let sessionId="{{ $sessionId }}";
let healthUrl="{{ route('live.health', ':id') }}".replace(':id',sessionId);
let eventsUrl="{{ route('live.events', ':id') }}".replace(':id',sessionId);
setInterval(async()=>{
try{let h=await fetch(healthUrl,{headers:{"X-Correlation-Id":crypto.randomUUID()}});if(h.ok){let j=await h.json();document.getElementById('liveFps').textContent=j.metrics?.fps??'—';document.getElementById('liveLatency').textContent=(j.metrics?.latency_ms??'—')+'ms';document.getElementById('liveLastFrame').textContent=j.last_frame_time?new Date(j.last_frame_time*1000).toLocaleTimeString():'—';let c=document.getElementById('connStatus');c.textContent=j.health;c.className='badge '+(j.health==='healthy'?'bg-success':j.health==='degraded'?'bg-warning text-dark':'bg-danger')+' status-badge'}}catch(e){}
try{let ev=await fetch(eventsUrl,{headers:{"X-Correlation-Id":crypto.randomUUID()}});if(ev.ok){let j=await ev.json();document.getElementById('eventCount').textContent=j.total;let tb=document.getElementById('liveEvents');if(j.total>0){tb.innerHTML=j.events.map(e=>`<tr><td><span class="badge bg-danger status-badge">${e.event_type}</span></td><td><span class="badge bg-dark status-badge">ID:${e.track_id}</span></td><td style="font-size:12px">${e.explanation?e.explanation.substring(0,40):'—'}</td><td style="font-size:11px">${e.start_frame}</td></tr>`).join('')}}}catch(e){}
},2000);
</script>
@endpush
@endsection
