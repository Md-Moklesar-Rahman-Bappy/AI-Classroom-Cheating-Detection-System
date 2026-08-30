@extends("layouts.bootstrap")
@section("title","Review")
@section("content")
<div class="d-flex justify-content-between align-items-center mb-4">
    <div><h2 class="mb-1" style="font-weight:700;">Review — {{ $detectionEvent->event_type }}</h2><p class="text-muted mb-0" style="font-size:13px;">Human review required — AI observation is not proof</p></div>
    <span class="badge @if($detectionEvent->review_status=="pending") bg-warning text-dark @elseif($detectionEvent->review_status=="confirmed_suspicious") bg-danger @else bg-success @endif status-badge" style="font-size:13px;"><i class="bi @if($detectionEvent->review_status=="pending") bi-hourglass @elseif($detectionEvent->review_status=="confirmed_suspicious") bi-exclamation-triangle @else bi-check2 @endif me-1"></i>{{ $detectionEvent->review_status }}</span>
</div>

<div class="row g-4">
    <div class="col-12 col-lg-4">
        <div class="card h-100">
            <div class="card-header bg-white" style="border-bottom:1px solid #e2e8f0;"><h5 class="mb-0" style="font-size:13px;letter-spacing:0.06em;text-transform:uppercase;"><i class="bi bi-cpu me-2 text-primary"></i>Machine Observation</h5></div>
            <div class="card-body" style="font-size:13px;">
                <div class="d-flex justify-content-between mb-2"><span class="text-muted">Track</span><span class="badge bg-dark status-badge"><i class="bi bi-bullseye me-1"></i>ID:{{ $detectionEvent->temporary_track_id }}</span></div>
                <div class="d-flex justify-content-between mb-2"><span class="text-muted">Type</span><span class="badge bg-info status-badge">{{ $detectionEvent->event_type }}</span></div>
                <div class="d-flex justify-content-between mb-2"><span class="text-muted">Status</span><span class="badge bg-secondary status-badge">{{ $detectionEvent->event_status }}</span></div>
                <div class="d-flex justify-content-between mb-2"><span class="text-muted">Confidence</span><span style="font-variant-numeric:tabular-nums;">{{ $detectionEvent->confidence ?? "—" }} <span class="badge bg-secondary status-badge">text+color</span></span></div>
                <hr style="border-color:#f1f5f9;">
                <div style="font-size:12px;color:#64748b;">Frames {{ $detectionEvent->started_at_frame ?? "—" }} – {{ $detectionEvent->ended_at_frame ?? "—" }}<br>Seconds {{ $detectionEvent->started_at_seconds ?? "—" }} – {{ $detectionEvent->ended_at_seconds ?? "—" }}</div>
            </div>
        </div>
    </div>
    <div class="col-12 col-lg-4">
        <div class="card h-100">
            <div class="card-header bg-white" style="border-bottom:1px solid #e2e8f0;"><h5 class="mb-0" style="font-size:13px;letter-spacing:0.06em;text-transform:uppercase;"><i class="bi bi-file-earmark-bar-graph me-2 text-success"></i>Supporting Detector/Rule Evidence</h5></div>
            <div class="card-body" style="font-size:13px;">
                <div class="d-flex justify-content-between mb-2"><span class="text-muted">Model</span><span>{{ $detectionEvent->model_version_id }}</span></div>
                <div class="d-flex justify-content-between mb-2"><span class="text-muted">Rule Score</span><span>{{ $detectionEvent->rule_score ?? "—" }}</span></div>
                <div class="d-flex justify-content-between mb-3"><span class="text-muted">Evidence</span>@if($detectionEvent->evidence_available)<span class="badge bg-success status-badge">Available</span>@else<span class="badge bg-secondary status-badge">Not yet</span>@endif</div>
                <div class="alert alert-warning py-2" style="font-size:12px;"><i class="bi bi-shield-exclamation me-1"></i><strong>Machine observation only — not proof of misconduct.</strong></div>
                @if($detectionEvent->evidences->isNotEmpty())
                    <div class="list-group list-group-flush">
                        @foreach($detectionEvent->evidences as $ev)
                        <a href="{{ route("evidence.show",$ev) }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center" style="font-size:13px;">
                            <span><i class="bi bi-image me-2 text-muted"></i>Evidence #{{ $ev->id }} — {{ $ev->file_type }}</span><span class="badge bg-light text-dark border">View</span>
                        </a>
                        @endforeach
                    </div>
                    <div class="text-muted mt-2" style="font-size:11px;"><i class="bi bi-lock me-1"></i>Not in public directory — protected access</div>
                @endif
            </div>
        </div>
    </div>
    <div class="col-12 col-lg-4">
        <div class="card h-100">
            <div class="card-header bg-white" style="border-bottom:1px solid #e2e8f0;"><h5 class="mb-0" style="font-size:13px;letter-spacing:0.06em;text-transform:uppercase;"><i class="bi bi-person-check me-2 text-info"></i>Human Decision</h5></div>
            <div class="card-body" style="font-size:13px;">
                <div class="d-flex justify-content-between mb-2"><span class="text-muted">Review Status</span><span class="badge @if($detectionEvent->review_status=="pending") bg-warning text-dark @elseif($detectionEvent->review_status=="confirmed_suspicious") bg-danger @else bg-success @endif status-badge">{{ $detectionEvent->review_status }}</span></div>
                <div class="d-flex justify-content-between mb-2"><span class="text-muted">Reviewer</span><span>{{ $detectionEvent->reviewed_by ?? "—" }}</span></div>
                <div class="mb-3"><div class="text-muted" style="font-size:11px;letter-spacing:0.06em;text-transform:uppercase;">Note</div><div class="bg-light rounded p-2" style="font-size:13px;min-height:40px;">{{ $detectionEvent->reviewer_note ?? "—" }}</div></div>
                <form method="POST" action="{{ route("detection-events.review",$detectionEvent) }}">
                    @csrf
                    <div class="mb-2"><label class="form-label" style="font-size:12px;">Decision</label><select name="decision" class="form-select" required><option value="confirmed_suspicious">confirmed_suspicious</option><option value="dismissed_normal">dismissed_normal</option><option value="needs_further_review">needs_further_review</option></select></div>
                    <div class="mb-3"><label class="form-label" style="font-size:12px;">Note</label><input type="text" name="note" class="form-control" placeholder="Required for confirmed/needs"></div>
                    <button class="btn btn-primary w-100"><i class="bi bi-check2 me-1"></i> Submit Review</button>
                </form>
                <div class="text-muted mt-2" style="font-size:11px;">All decisions are append-only and audited.</div>
            </div>
        </div>
    </div>
</div>

<div class="card mt-4">
    <div class="card-header bg-white d-flex justify-content-between align-items-center" style="border-bottom:1px solid #e2e8f0;">
        <h5 class="mb-0" style="font-size:13px;letter-spacing:0.06em;text-transform:uppercase;"><i class="bi bi-clock-history me-2 text-muted"></i>Audit History</h5>
        <a href="{{ route("audit-logs.index") }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-journal-text me-1"></i> View Audit Logs</a>
    </div>
    <div class="card-body">
        <p class="text-muted mb-0" style="font-size:13px;">All review decisions are append-only and audited. Check audit logs for full history with actor, IP, and correlation ID.</p>
    </div>
</div>
@endsection
