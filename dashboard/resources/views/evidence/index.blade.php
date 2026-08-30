@extends("layouts.bootstrap")
@section("title","Evidence")
@section("content")
<div class="d-flex justify-content-between align-items-center mb-4">
    <div><h2 class="mb-1" style="font-weight:700;">Evidence</h2><p class="text-muted mb-0" style="font-size:13px;">Incident-only snapshots — stored outside public path, protected access</p></div>
    <span class="badge bg-dark status-badge"><i class="bi bi-shield-lock me-1"></i> Protected</span>
</div>

<div class="card mb-3">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0" style="font-size:14px;font-weight:600;"><i class="bi bi-file-earmark-bar-graph me-2 text-primary"></i>Event #{{ $detectionEvent->id }} — {{ $detectionEvent->event_type }}</h5>
        <span class="badge @if($detectionEvent->review_status=="pending") bg-warning text-dark @elseif($detectionEvent->review_status=="confirmed_suspicious") bg-danger @else bg-success @endif status-badge">{{ $detectionEvent->review_status }}</span>
    </div>
    <div class="card-body">
        <div class="row g-3" style="font-size:13px;">
            <div class="col-md-4"><strong>Track</strong> <span class="badge bg-dark">ID:{{ $detectionEvent->temporary_track_id }}</span></div>
            <div class="col-md-4"><strong>Type</strong> <span class="badge bg-info">{{ $detectionEvent->event_type }}</span></div>
            <div class="col-md-4"><strong>Frames</strong> {{ $detectionEvent->started_at_frame ?? "—" }} – {{ $detectionEvent->ended_at_frame ?? "—" }}</div>
        </div>
    </div>
</div>

@if($evidences->isEmpty())
    <div class="card p-5 text-center">
        <div class="mx-auto mb-3 d-flex align-items-center justify-content-center" style="width:48px;height:48px;background:#fef3c7;border-radius:12px;"><i class="bi bi-image text-warning" style="font-size:20px;"></i></div>
        <h5>No evidence yet</h5><p class="text-muted" style="font-size:13px;">Evidence is incident-only. A snapshot will appear when the rule threshold is met and the snapshot is captured.</p>
        <div class="alert alert-info py-2" style="font-size:12px;">Files are stored outside <code>public/</code> and served via authorized controller with audit.</div>
    </div>
@else
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 style="font-size:14px;font-weight:600;">Snapshots ({{ $evidences->count() }})</h5>
        <div class="btn-group btn-group-sm"><button class="btn btn-outline-secondary active"><i class="bi bi-grid"></i> Grid</button><button class="btn btn-outline-secondary"><i class="bi bi-list"></i> List</button></div>
    </div>
    <div class="row g-3">
        @foreach($evidences as $ev)
        <div class="col-12 col-md-6 col-lg-4">
            <div class="card h-100">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <span class="badge bg-primary status-badge"><i class="bi bi-camera me-1"></i> {{ $ev->file_type }}</span>
                        <span class="text-muted" style="font-size:11px;">#{{ $ev->id }}</span>
                    </div>
                    <div class="bg-light rounded d-flex align-items-center justify-content-center mb-3" style="height:120px;"><i class="bi bi-image text-muted" style="font-size:32px;"></i></div>
                    <div style="font-size:13px;"><div><strong>Frame</strong> {{ $ev->frame_number ?? "—" }} <span class="text-muted">at {{ $ev->captured_at_seconds ?? "—" }}s</span></div><div class="text-muted" style="font-size:12px;">{{ $ev->width ?? "—" }}×{{ $ev->height ?? "—" }} — checksum {{ Str::limit($ev->checksum_sha256 ?? "—",12) }}</div></div>
                </div>
                <div class="card-footer bg-white d-flex justify-content-between align-items-center">
                    <span class="text-muted" style="font-size:11px;"><i class="bi bi-lock me-1"></i> Protected</span>
                    <a href="{{ route("evidence.show",$ev) }}" class="btn btn-sm btn-primary"><i class="bi bi-eye me-1"></i> View</a>
                </div>
            </div>
        </div>
        @endforeach
    </div>
@endif
@endsection
