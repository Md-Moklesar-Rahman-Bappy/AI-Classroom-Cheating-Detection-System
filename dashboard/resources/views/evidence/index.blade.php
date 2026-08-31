@extends("layouts.bootstrap")
@section("title","Evidence")
@section("content")
<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
<div><h1 class="h4 mb-1" style="font-weight:700;letter-spacing:-.02em">Evidence</h1><p class="text-muted mb-0" style="font-size:13px">Incident-only snapshots — protected gallery, authorized access</p></div>
<span class="badge bg-dark d-inline-flex align-items-center gap-1 status-badge"><i class="bi bi-shield-lock" aria-hidden="true"></i> Protected</span>
</div>

<div class="card mb-4">
<div class="card-header bg-white d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-2">
<h2 class="h6 mb-0" style="font-size:13px"><i class="bi bi-file-earmark-bar-graph me-2 text-primary" aria-hidden="true"></i>Event #{{ Str::limit($detectionEvent->id,8) }} — {{ $detectionEvent->event_type }}</h2>
<span class="badge @if($detectionEvent->review_status=="pending") bg-warning text-dark @elseif($detectionEvent->review_status=="confirmed_suspicious") bg-danger @else bg-success @endif status-badge"><i class="bi @if($detectionEvent->review_status=="pending") bi-hourglass @elseif($detectionEvent->review_status=="confirmed_suspicious") bi-exclamation-triangle @else bi-check-circle @endif me-1" aria-hidden="true"></i>{{ $detectionEvent->review_status }}</span>
</div>
<div class="card-body">
<div class="row g-3" style="font-size:13px">
<div class="col-6 col-md-4"><div class="text-muted" style="font-size:11px;letter-spacing:.06em;text-transform:uppercase">Track</div><span class="badge bg-dark status-badge">ID:{{ $detectionEvent->temporary_track_id }}</span></div>
<div class="col-6 col-md-4"><div class="text-muted" style="font-size:11px;letter-spacing:.06em;text-transform:uppercase">Type</div><span class="badge bg-primary status-badge">{{ $detectionEvent->event_type }}</span></div>
<div class="col-12 col-md-4"><div class="text-muted" style="font-size:11px;letter-spacing:.06em;text-transform:uppercase">Frames</div><span style="font-variant-numeric:tabular-nums">{{ $detectionEvent->started_at_frame ?? "—" }} – {{ $detectionEvent->ended_at_frame ?? "—" }}</span></div>
</div>
</div>
</div>

@if($evidences->isEmpty())
<div class="card empty-state">
<div class="empty-icon" style="background:#fef3c7;color:#d97706"><i class="bi bi-image" aria-hidden="true"></i></div>
<h2 class="h5">No evidence yet</h2>
<p class="text-muted mx-auto" style="max-width:420px;font-size:13px">Evidence is incident-only. A snapshot appears when the rule threshold is met.</p>
<div class="alert alert-info py-2 mx-auto" style="font-size:12px;max-width:420px"><i class="bi bi-info-circle me-1" aria-hidden="true"></i> Files stored outside <code class="text-mono">public/</code> and served via authorized controller with audit.</div>
</div>
@else
<div class="d-flex justify-content-between align-items-center mb-3">
<h2 class="h6 mb-0" style="font-weight:600">Snapshots ({{ $evidences->count() }})</h2>
<div class="btn-group btn-group-sm" role="group" aria-label="View toggle"><button class="btn btn-outline-secondary active focus-ring" aria-pressed="true"><i class="bi bi-grid" aria-hidden="true"></i> Grid</button><button class="btn btn-outline-secondary focus-ring"><i class="bi bi-list" aria-hidden="true"></i> List</button></div>
</div>
<div class="row g-3">
@foreach($evidences as $ev)
<div class="col-12 col-md-6 col-lg-4">
<div class="card h-100">
<div class="card-body p-3">
<div class="d-flex justify-content-between align-items-start mb-2">
<span class="badge bg-primary status-badge"><i class="bi bi-camera me-1" aria-hidden="true"></i> {{ $ev->file_type }}</span>
<code class="text-mono text-muted" style="font-size:11px">#{{ Str::limit($ev->id,8) }}</code>
</div>
<div class="bg-light rounded d-flex align-items-center justify-content-center mb-3" style="height:120px" role="img" aria-label="Evidence snapshot placeholder"><i class="bi bi-image text-muted" style="font-size:32px" aria-hidden="true"></i></div>
<div style="font-size:13px"><div><strong>Frame</strong> <span style="font-variant-numeric:tabular-nums">{{ $ev->frame_number ?? "—" }}</span> <span class="text-muted">at {{ $ev->captured_at_seconds ?? "—" }}s</span></div><div class="text-muted text-mono d-flex align-items-center gap-1" style="font-size:11px">{{ $ev->width ?? "—" }}×{{ $ev->height ?? "—" }} • {{ Str::limit($ev->checksum_sha256 ?? "—",12) }} @if($ev->checksum_sha256)<button class="btn btn-sm btn-link p-0" onclick="navigator.clipboard.writeText('{{ $ev->checksum_sha256 }}')" aria-label="Copy checksum"><i class="bi bi-copy" style="font-size:11px" aria-hidden="true"></i></button>@endif</div></div>
</div>
<div class="card-footer bg-white d-flex justify-content-between align-items-center">
<span class="text-muted d-inline-flex align-items-center gap-1" style="font-size:11px"><i class="bi bi-lock" aria-hidden="true"></i> Protected</span>
<a href="{{ route("evidence.show",$ev) }}" class="btn btn-sm btn-primary focus-ring"><i class="bi bi-eye me-1" aria-hidden="true"></i> View</a>
</div>
</div>
</div>
@endforeach
</div>
@endif
@endsection
