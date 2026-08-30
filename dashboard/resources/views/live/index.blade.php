@extends("layouts.bootstrap")
@section("title","Live Surveillance")
@section("content")
<div class="d-flex justify-content-between align-items-center mb-4">
    <div><h2 class="mb-1" style="font-weight:700;">Live Surveillance</h2><p class="text-muted mb-0" style="font-size:13px;">Secure live monitoring — single source, low-resource</p></div>
    <span class="badge bg-secondary status-badge">Phase 7 — Webcam/Test</span>
</div>

<div class="card p-4 mb-4">
    <h5 style="font-size:14px;font-weight:600;"><i class="bi bi-play-circle me-2 text-primary"></i>Start Monitoring</h5>
    <form method="POST" action="{{ route("live.start") }}">
        @csrf
        <div class="row g-3">
            <div class="col-md-4"><label class="form-label" style="font-size:12px;letter-spacing:0.06em;text-transform:uppercase;">Exam Session</label><select name="exam_session_id" class="form-select" required><option value="">Select session</option>@foreach($sessions as $s)<option value="{{ $s->id }}">{{ $s->name }} — {{ $s->room->name ?? "No room" }}</option>@endforeach</select></div>
            <div class="col-md-3"><label class="form-label" style="font-size:12px;letter-spacing:0.06em;text-transform:uppercase;">Camera</label><select name="camera_source_id" class="form-select"><option value="">— Test/Webcam —</option>@foreach($cameras as $c)<option value="{{ $c->id }}">{{ $c->name }} ({{ $c->source_type }})</option>@endforeach</select></div>
            <div class="col-md-2"><label class="form-label" style="font-size:12px;letter-spacing:0.06em;text-transform:uppercase;">Source</label><select name="source_type" class="form-select" required><option value="webcam">webcam</option><option value="test">test</option><option value="rtsp">rtsp (verified only)</option></select></div>
            <div class="col-md-3"><label class="form-label" style="font-size:12px;letter-spacing:0.06em;text-transform:uppercase;">Identifier</label><input type="text" name="identifier" class="form-control" value="0" required placeholder="0 for webcam, or test"><div class="form-text" style="font-size:11px;">Never expose credentials — identifier only</div></div>
        </div>
        <div class="mt-3 d-flex gap-2"><button class="btn btn-danger"><i class="bi bi-broadcast me-1"></i> Start Monitoring</button><span class="text-muted" style="font-size:12px;">Single-source limit — one active live session at a time</span></div>
    </form>
</div>

<div class="card">
    <div class="card-header bg-white d-flex justify-content-between align-items-center" style="border-bottom:1px solid #e2e8f0;">
        <h5 class="mb-0" style="font-size:13px;letter-spacing:0.06em;text-transform:uppercase;"><i class="bi bi-camera-video me-2"></i>Live Sessions</h5>
        <span class="badge bg-light text-dark border">{{ $cameras->count() }} cameras</span>
    </div>
    <div class="card-body">
        <p class="text-muted" style="font-size:13px;">Live mode uses shared engine (detector, tracker, orientation, temporal rules) without duplication. Verified sources: <strong>local webcam (device 0)</strong> and <strong>test stream</strong>. EZVIZ RTSP unverified — see compatibility report.</p>
        <div class="alert alert-info py-2" style="font-size:12px;"><i class="bi bi-info-circle me-1"></i> No active live sessions. Start monitoring above to see preview, metrics, and alerts.</div>
    </div>
</div>
@endsection
