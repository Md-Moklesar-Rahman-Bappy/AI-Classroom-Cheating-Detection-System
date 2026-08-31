@extends("layouts.bootstrap")
@section("title","Cameras")
@section("content")
<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
<div><h1 class="h4 mb-1" style="font-weight:700;letter-spacing:-.02em">Camera Sources</h1><p class="text-muted mb-0" style="font-size:13px">Encrypted credentials — never displayed, identifier only</p></div>
<a href="{{ route("camera-sources.create") }}" class="btn btn-primary focus-ring"><i class="bi bi-plus-lg me-1" aria-hidden="true"></i> Add Camera</a>
</div>

<div class="alert alert-warning d-flex gap-2 py-2" style="font-size:13px"><i class="bi bi-shield-lock flex-shrink-0" aria-hidden="true"></i><div><strong>Security:</strong> Credentials are encrypted with APP_KEY (AES-256-GCM) and never displayed or logged.</div></div>

@if($sources->isEmpty())
<div class="card empty-state">
<div class="empty-icon"><i class="bi bi-camera-video" aria-hidden="true"></i></div>
<h2 class="h5">No cameras</h2>
<p class="text-muted mx-auto" style="max-width:420px;font-size:13px">Add a test source for development or a verified RTSP source. Credentials are encrypted at rest.</p>
<a href="{{ route("camera-sources.create") }}" class="btn btn-primary mt-2">Add first camera</a>
</div>
@else
<div class="card d-none d-md-block">
<div class="table-responsive">
<table class="table table-hover align-middle mb-0" style="font-size:13px">
<caption class="visually-hidden">Camera sources — name, type, identifier, status, credentials</caption>
<thead><tr><th>Name</th><th>Type</th><th>Identifier</th><th>Status</th><th>Credentials</th><th style="width:160px">Actions</th></tr></thead>
<tbody>
@foreach($sources as $s)
<tr>
<td><div class="fw-medium truncate" style="max-width:180px" title="{{ $s->name }}">{{ $s->name }}</div><div class="text-muted text-mono" style="font-size:11px">ID {{ Str::limit($s->id,8) }}</div></td>
<td><span class="badge bg-light text-dark border status-badge"><i class="bi bi-camera-video me-1" aria-hidden="true"></i>{{ $s->source_type }}</span></td>
<td><code class="text-mono" title="{{ $s->identifier }}">{{ Str::limit($s->identifier,34) }}</code> <button class="btn btn-sm btn-link p-0 ms-1" onclick="navigator.clipboard.writeText('{{ $s->identifier }}')" aria-label="Copy identifier" title="Copy"><i class="bi bi-copy" style="font-size:12px" aria-hidden="true"></i></button></td>
<td><span class="badge @if($s->status=="active") bg-success @elseif($s->status=="testing") bg-warning text-dark @elseif($s->status=="error") bg-danger @else bg-secondary @endif status-badge"><i class="bi @if($s->status=="active") bi-check-circle @elseif($s->status=="error") bi-x-circle @else bi-circle @endif me-1" aria-hidden="true"></i>{{ $s->status }}</span></td>
<td>@if($s->has_credentials)<span class="badge bg-success status-badge"><i class="bi bi-shield-lock me-1" aria-hidden="true"></i> Encrypted</span>@else<span class="badge bg-light text-dark border status-badge"><i class="bi bi-dash-circle me-1" aria-hidden="true"></i> None</span>@endif</td>
<td>
<div class="btn-group btn-group-sm" role="group" aria-label="Camera actions">
<a href="{{ route("camera-sources.show",$s) }}" class="btn btn-outline-primary focus-ring">View</a>
<a href="{{ route("camera-sources.edit",$s) }}" class="btn btn-outline-secondary focus-ring">Edit</a>
</div>
</td>
</tr>
@endforeach
</tbody>
</table>
</div>
<div class="card-footer bg-white d-flex flex-column flex-sm-row justify-content-between align-items-center gap-2" style="font-size:12px;color:var(--color-text-muted)"><span>Showing {{ $sources->firstItem() }}–{{ $sources->lastItem() }} of {{ $sources->total() }}</span>{{ $sources->links() }}</div>
</div>
<div class="d-md-none">
@foreach($sources as $s)
<div class="card p-3 mb-2">
<div class="d-flex justify-content-between align-items-start gap-2"><div class="fw-medium truncate">{{ $s->name }}</div><span class="badge @if($s->status=="active") bg-success @else bg-secondary @endif status-badge">{{ $s->status }}</span></div>
<div class="text-muted text-mono mt-1" style="font-size:11px">{{ $s->source_type }} • {{ Str::limit($s->identifier,28) }}</div>
<div class="d-flex gap-2 mt-3"><a href="{{ route("camera-sources.show",$s) }}" class="btn btn-sm btn-outline-primary">View</a><a href="{{ route("camera-sources.edit",$s) }}" class="btn btn-sm btn-outline-secondary">Edit</a></div>
</div>
@endforeach
<div class="d-flex justify-content-center mt-3">{{ $sources->links() }}</div>
</div>
@endif
@endsection
