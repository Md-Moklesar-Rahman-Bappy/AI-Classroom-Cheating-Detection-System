@extends("layouts.bootstrap")
@section("title","Add Camera")
@section("content")
<h2>Add Camera Source (Metadata Only)</h2>
<form method="POST" action="{{ route("camera-sources.store") }}">@csrf
<div class="mb-3"><label class="form-label">Name</label><input type="text" name="name" class="form-control" required></div>
<div class="mb-3"><label class="form-label">Type</label><select name="source_type" class="form-select"><option value="webcam">webcam</option><option value="rtsp">rtsp</option><option value="test_source">test_source</option><option value="video_file">video_file</option></select></div>
<div class="mb-3"><label class="form-label">Identifier (device index or URL without credentials)</label><input type="text" name="identifier" class="form-control" required placeholder="0 or rtsp://host/stream"></div>
<div class="mb-3"><label class="form-label">Credentials (encrypted placeholder, not displayed after save)</label><input type="password" name="credentials" class="form-control" placeholder="Optional"></div>
<div class="mb-3"><label class="form-label">Session</label><select name="exam_session_id" class="form-select"><option value="">— None —</option>@foreach($sessions as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach</select></div>
<button class="btn btn-primary">Create</button>
</form>
<p class="text-muted small mt-2">Do not expose decrypted credentials in views or API output. EZVIZ integration not attempted in Phase 5.</p>
@endsection
