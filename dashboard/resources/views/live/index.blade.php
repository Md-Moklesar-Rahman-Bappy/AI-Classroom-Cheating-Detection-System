@extends("layouts.bootstrap")
@section("title","Live Surveillance")
@section("content")
<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
<div><h1 class="h4 mb-1" style="font-weight:700;letter-spacing:-.02em">Live Surveillance</h1><p class="text-muted mb-0" style="font-size:13px">Single-source monitoring — webcam or test stream, no credential exposure</p></div>
<span class="badge bg-light text-dark border d-inline-flex align-items-center gap-1 status-badge"><i class="bi bi-broadcast text-danger" aria-hidden="true"></i> Phase 7 — Webcam/Test</span>
</div>

<div class="card p-3 p-md-4 mb-4">
<div class="d-flex align-items-center gap-2 mb-3"><span class="d-flex align-items-center justify-content-center" style="width:28px;height:28px;background:var(--color-danger-soft);color:var(--color-danger);border-radius:8px"><i class="bi bi-play-circle" aria-hidden="true"></i></span><h2 class="h6 mb-0" style="font-weight:600">Start Monitoring</h2><span class="badge bg-warning text-dark ms-auto status-badge"><i class="bi bi-exclamation-triangle me-1" aria-hidden="true"></i> One active source at a time</span></div>
<form method="POST" action="{{ route("live.start") }}">
@csrf
<div class="row g-3">
<div class="col-12 col-md-4">
<label for="exam_session_id" class="form-label" style="font-size:11px;letter-spacing:.06em;text-transform:uppercase;color:var(--color-text-muted);font-weight:600">Exam Session <span class="text-danger" aria-hidden="true">*</span></label>
<select id="exam_session_id" name="exam_session_id" class="form-select focus-ring" required aria-required="true">
<option value="">Select session</option>
@foreach($sessions as $s)<option value="{{ $s->id }}" @selected(old('exam_session_id')==$s->id)>{{ $s->name }} — {{ $s->room->name ?? "No room" }}</option>@endforeach
</select>
@error('exam_session_id')<div class="invalid-feedback d-block" style="font-size:12px">{{ $message }}</div>@enderror
</div>
<div class="col-12 col-md-3">
<label for="camera_source_id" class="form-label" style="font-size:11px;letter-spacing:.06em;text-transform:uppercase;color:var(--color-text-muted);font-weight:600">Camera</label>
<select id="camera_source_id" name="camera_source_id" class="form-select focus-ring">
<option value="">— Test/Webcam —</option>
@foreach($cameras as $c)<option value="{{ $c->id }}" @selected(old('camera_source_id')==$c->id)>{{ $c->name }} ({{ $c->source_type }})</option>@endforeach
</select>
</div>
<div class="col-6 col-md-2">
<label for="source_type" class="form-label" style="font-size:11px;letter-spacing:.06em;text-transform:uppercase;color:var(--color-text-muted);font-weight:600">Source <span class="text-danger" aria-hidden="true">*</span></label>
<select id="source_type" name="source_type" class="form-select focus-ring" required><option value="webcam" @selected(old('source_type')=='webcam')>webcam</option><option value="test" @selected(old('source_type')=='test')>test</option><option value="rtsp" @selected(old('source_type')=='rtsp')>rtsp (verified only)</option></select>
</div>
<div class="col-6 col-md-3">
<label for="identifier" class="form-label" style="font-size:11px;letter-spacing:.06em;text-transform:uppercase;color:var(--color-text-muted);font-weight:600">Identifier <span class="text-danger" aria-hidden="true">*</span></label>
<input id="identifier" type="text" name="identifier" class="form-control focus-ring" value="{{ old('identifier','0') }}" required placeholder="0 for webcam" aria-describedby="identifierHelp">
<div id="identifierHelp" class="form-text" style="font-size:11px">Never expose credentials — identifier only.</div>
</div>
</div>
<div class="d-flex flex-column flex-sm-row gap-2 align-items-sm-center mt-3">
<button class="btn btn-danger focus-ring"><i class="bi bi-broadcast me-1" aria-hidden="true"></i> Start Monitoring</button>
<span class="text-muted" style="font-size:12px"><i class="bi bi-info-circle me-1" aria-hidden="true"></i> Enforces single-source limit. Shared engine (detector, tracker, rules) — no duplication.</span>
</div>
</form>
</div>

<div class="row g-4">
<div class="col-12 col-lg-5">
<div class="card h-100">
<div class="card-header bg-white d-flex justify-content-between align-items-center"><h2 class="h6 mb-0" style="font-size:11px;letter-spacing:.06em;text-transform:uppercase"><i class="bi bi-camera-video me-2" aria-hidden="true"></i>Preview</h2><span class="badge bg-light text-dark border status-badge">320×180 • MJPEG + polling</span></div>
<div class="bg-dark d-flex align-items-center justify-content-center" style="height:200px" role="img" aria-label="Live preview placeholder">
<div class="text-center text-white-50 p-3"><i class="bi bi-camera-video" style="font-size:32px" aria-hidden="true"></i><div class="mt-2" style="font-size:12px">Preview appears after starting monitoring</div><div class="text-white-50" style="font-size:11px">Low-resource 320×180 — separate from detection</div></div>
</div>
<div class="card-body" style="font-size:13px">
<div class="d-flex flex-wrap gap-2 mb-2">
<span class="badge bg-light text-dark border status-badge"><i class="bi bi-cpu me-1" aria-hidden="true"></i> Shared engine</span>
<span class="badge bg-success status-badge"><i class="bi bi-check-circle me-1" aria-hidden="true"></i> webcam verified</span>
<span class="badge bg-success status-badge"><i class="bi bi-check-circle me-1" aria-hidden="true"></i> test verified</span>
<span class="badge bg-warning text-dark status-badge"><i class="bi bi-question-circle me-1" aria-hidden="true"></i> RTSP unverified</span>
</div>
<p class="text-muted mb-0" style="font-size:12px">Verified: <strong>local webcam (device 0)</strong> and <strong>test stream</strong>. EZVIZ RTSP see compatibility report — not assumed supported.</p>
</div>
</div>
</div>
<div class="col-12 col-lg-3">
<div class="card h-100">
<div class="card-header bg-white"><h2 class="h6 mb-0" style="font-size:11px;letter-spacing:.06em;text-transform:uppercase"><i class="bi bi-info-circle me-2 text-primary" aria-hidden="true"></i>Source Info</h2></div>
<div class="card-body" style="font-size:13px">
<div class="d-flex justify-content-between mb-2"><span class="text-muted">Active sources</span><span class="badge bg-dark status-badge">1 max</span></div>
<div class="d-flex justify-content-between mb-2"><span class="text-muted">Mode</span><span class="badge bg-light text-dark border status-badge">low-resource</span></div>
<div class="d-flex justify-content-between mb-2"><span class="text-muted">Credentials</span><span class="badge bg-success status-badge"><i class="bi bi-shield-lock me-1" aria-hidden="true"></i> Encrypted</span></div>
<div class="alert alert-info py-2 mb-0" style="font-size:12px"><i class="bi bi-shield-check me-1" aria-hidden="true"></i> No credential in logs or URLs — identifier only.</div>
</div>
</div>
</div>
<div class="col-12 col-lg-4">
<div class="card h-100">
<div class="card-header bg-white"><h2 class="h6 mb-0" style="font-size:11px;letter-spacing:.06em;text-transform:uppercase"><i class="bi bi-speedometer me-2 text-success" aria-hidden="true"></i>Metrics</h2></div>
<div class="card-body text-center" style="font-size:13px">
<div class="empty-state" style="padding:16px"><div class="empty-icon" style="width:36px;height:36px;font-size:16px"><i class="bi bi-hourglass-split" aria-hidden="true"></i></div><p class="text-muted mb-1" style="font-size:13px">No active live sessions</p><p class="text-muted mb-0" style="font-size:12px">Start monitoring to see FPS, latency and alerts polling <code>/health</code> every 2s.</p></div>
<div class="d-flex justify-content-between text-start mt-3" style="font-size:12px"><span class="text-muted">Cameras</span><span class="badge bg-light text-dark border status-badge">{{ $cameras->count() }}</span></div>
<div class="d-flex justify-content-between text-start" style="font-size:12px"><span class="text-muted">Sessions</span><span class="badge bg-light text-dark border status-badge">{{ $sessions->count() }}</span></div>
</div>
</div>
</div>
</div>
@endsection
