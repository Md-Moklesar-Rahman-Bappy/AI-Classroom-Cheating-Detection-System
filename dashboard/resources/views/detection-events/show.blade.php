@extends("layouts.bootstrap")
@section("title","Review")
@section("content")
<div class="d-flex justify-content-between align-items-center mb-4">
    <div><h2 class="mb-1" style="font-weight:700;">Review — {{ $detectionEvent->event_type }}</h2><p class="text-muted mb-0" style="font-size:13px;">Human review required — AI observation is not proof</p></div>
    <span class="badge @if($detectionEvent->review_status=="pending") bg-warning text-dark @elseif($detectionEvent->review_status=="confirmed_suspicious") bg-danger @else bg-success @endif status-badge" style="font-size:13px;"><i class="bi @if($detectionEvent->review_status=="pending") bi-hourglass @elseif($detectionEvent->review_status=="confirmed_suspicious") bi-exclamation-triangle @else bi-check2 @endif me-1"></i>{{ $detectionEvent->review_status }}</span>
</div>

@if($detectionEvent->evidences->isNotEmpty())
<div class="card mb-4">
    <div class="card-header bg-white d-flex justify-content-between align-items-center" style="border-bottom:1px solid #e2e8f0;">
        <h5 class="mb-0" style="font-size:13px;letter-spacing:0.06em;text-transform:uppercase;"><i class="bi bi-image me-2 text-primary"></i>Annotated Evidence — Track #{{ $detectionEvent->temporary_track_id }} Highlighted</h5>
        <span class="badge bg-dark">Frame {{ $detectionEvent->started_at_frame ?? $detectionEvent->ended_at_frame ?? "—" }} — {{ number_format($detectionEvent->started_at_seconds ?? 0,1) }}s</span>
    </div>
    <div class="card-body p-3">
        @foreach($detectionEvent->evidences as $ev)
        <div class="evidence-viewer mb-3" style="position:relative;overflow:hidden;border-radius:8px;border:2px solid #e2e8f0;background:#0f172a;">
            <img src="{{ route("evidence.show",$ev) }}" alt="Annotated evidence Track #{{ $detectionEvent->temporary_track_id }} {{ $detectionEvent->event_type }}" style="width:100%;height:auto;display:block;cursor:zoom-in;transition:transform 0.3s;" onclick="this.style.transform=this.style.transform==='scale(2)'?'scale(1)':'scale(2)';this.style.cursor=this.style.cursor==='zoom-in'?'zoom-out':'zoom-in'" title="Click to zoom">
            <div style="position:absolute;bottom:8px;left:8px;background:rgba(0,0,0,0.75);color:#fff;padding:6px 10px;border-radius:6px;font-size:11px;line-height:1.4;">
                <div><strong>Track #{{ $detectionEvent->temporary_track_id }}</strong> — {{ $detectionEvent->event_type }}</div>
                <div>Frame {{ $ev->frame_number ?? "—" }} · {{ number_format($ev->captured_at_seconds ?? 0,1) }}s · {{ $ev->width }}×{{ $ev->height }}</div>
                <div style="opacity:0.7;">Click image to zoom · Only highlighted student triggered event</div>
            </div>
            <div style="position:absolute;top:8px;right:8px;display:flex;gap:6px;flex-wrap:wrap;">
                <a href="{{ route("evidence.show",$ev) }}" target="_blank" class="btn btn-sm btn-light" style="font-size:11px;">Full size</a>
                <a href="{{ route("evidence.download",$ev) }}?format=original" class="btn btn-sm btn-light" style="font-size:11px;">Original</a>
                <a href="{{ route("evidence.download",$ev) }}?format=jpg" class="btn btn-sm btn-primary" style="font-size:11px;"><i class="bi bi-download me-1"></i> JPG</a>
                <a href="{{ route("evidence.download",$ev) }}?format=png" class="btn btn-sm btn-primary" style="font-size:11px;">PNG</a>
                <a href="{{ route("evidence.download",$ev) }}?format=json" class="btn btn-sm btn-dark" style="font-size:11px;">JSON</a>
                @if(auth()->user()->hasAnyRole(['system_admin','exam_admin']))<form method="POST" action="{{ route("evidence.destroy",$ev) }}" class="d-inline delete-evidence-form">@csrf @method("DELETE")<button class="btn btn-sm btn-danger" style="font-size:11px;">Delete</button></form>@endif
            </div>
        </div>
        @endforeach
        <div class="alert alert-info py-2 mb-0" style="font-size:12px;"><i class="bi bi-info-circle me-1"></i> <strong>Color policy:</strong> <span style="display:inline-block;width:10px;height:10px;background:#00c800;border-radius:2px;vertical-align:middle;"></span> D1 Person &nbsp; <span style="display:inline-block;width:10px;height:10px;background:#ff0000;border-radius:2px;vertical-align:middle;"></span> D2 Phone &nbsp; <span style="display:inline-block;width:10px;height:10px;background:#ffa500;border-radius:2px;vertical-align:middle;"></span> B1/B2/B3 Looking &nbsp; <span style="display:inline-block;width:10px;height:10px;background:#ff0000;border-radius:2px;vertical-align:middle;"></span> B4 Departure &nbsp; <span style="display:inline-block;width:10px;height:10px;background:#a0a0a0;border-radius:2px;vertical-align:middle;"></span> Other students (gray)</div>
    </div>
</div>
@endif

<div class="row g-4">
    <div class="col-12 col-lg-4">
        <div class="card h-100">
            <div class="card-header bg-white" style="border-bottom:1px solid #e2e8f0;"><h5 class="mb-0" style="font-size:13px;letter-spacing:0.06em;text-transform:uppercase;"><i class="bi bi-cpu me-2 text-primary"></i>Machine Observation</h5></div>
            <div class="card-body" style="font-size:13px;">
                <div class="d-flex justify-content-between mb-2"><span class="text-muted">Track</span><span class="badge bg-dark status-badge"><i class="bi bi-bullseye me-1"></i>ID:{{ $detectionEvent->temporary_track_id }}</span></div>
                <div class="d-flex justify-content-between mb-2"><span class="text-muted">Type</span><span class="badge bg-info status-badge">{{ $detectionEvent->event_type }}</span></div>
                <div class="d-flex justify-content-between mb-2"><span class="text-muted">Code</span><span class="badge bg-secondary status-badge">{{ $detectionEvent->event_type }}</span></div>
                <div class="d-flex justify-content-between mb-2"><span class="text-muted">Status</span><span class="badge bg-secondary status-badge">{{ $detectionEvent->event_status }}</span></div>
                <div class="d-flex justify-content-between mb-2"><span class="text-muted">Detection Confidence</span><span style="font-variant-numeric:tabular-nums;">{{ $detectionEvent->confidence !== null ? number_format($detectionEvent->confidence,2) : "Not available" }} </span></div>
                <div class="d-flex justify-content-between mb-2"><span class="text-muted">Rule Score</span><span style="font-variant-numeric:tabular-nums;">{{ $detectionEvent->rule_score !== null ? number_format($detectionEvent->rule_score,2) : "Not available" }}</span></div>
                <div class="alert alert-light py-1 mt-2 mb-2" style="font-size:11px">Confidence describes the automated observation, not a final determination.</div>
                <hr style="border-color:#f1f5f9;">
                <div style="font-size:12px;color:#64748b;">Frames {{ $detectionEvent->started_at_frame ?? "—" }} – {{ $detectionEvent->ended_at_frame ?? "—" }}<br>Seconds {{ $detectionEvent->started_at_seconds ?? "—" }} – {{ $detectionEvent->ended_at_seconds ?? "—" }}</div>
                @if($detectionEvent->evidences->isNotEmpty())
                <hr style="border-color:#f1f5f9;">
                <div style="font-size:12px;"><strong>Annotated Evidence:</strong> Only <span class="badge bg-dark">Track #{{ $detectionEvent->temporary_track_id }}</span> highlighted with event color; other students gray outline.</div>
                @endif
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
                            <span><i class="bi bi-image me-2 text-muted"></i>Evidence #{{ $ev->id }} — Track #{{ $detectionEvent->temporary_track_id }} — {{ $detectionEvent->event_type }}</span><span class="badge bg-light text-dark border">View</span>
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

<div class="card mt-4 border-primary">
    <div class="card-header bg-white d-flex justify-content-between align-items-center" style="border-bottom:1px solid #e2e8f0;">
        <h5 class="mb-0" style="font-size:13px;letter-spacing:0.06em;text-transform:uppercase;"><i class="bi bi-lightbulb me-2 text-primary"></i>Explanation — Why This Alert Fired</h5>
        <span class="badge bg-primary">Human review required</span>
    </div>
    <div class="card-body">
        <div class="row g-3" style="font-size:13px;">
            <div class="col-6 col-md-3"><div class="text-muted" style="font-size:11px;letter-spacing:.06em;text-transform:uppercase;">Event</div><div class="fw-bold">{{ $detectionEvent->event_type }} ({{ $detectionEvent->event_type }})</div><div class="text-muted" style="font-size:11px;">{{ $detectionEvent->event_category ?? '—' }} · {{ $detectionEvent->event_status }}</div></div>
            <div class="col-6 col-md-3"><div class="text-muted" style="font-size:11px;letter-spacing:.06em;text-transform:uppercase;">Track</div><div class="fw-bold"><span class="badge bg-dark">Track #{{ $detectionEvent->temporary_track_id }}</span></div><div class="text-muted" style="font-size:11px;">Frame {{ $detectionEvent->started_at_frame ?? $detectionEvent->ended_at_frame ?? '—' }} · {{ number_format($detectionEvent->started_at_seconds ?? 0,1) }}s</div></div>
            <div class="col-6 col-md-3"><div class="text-muted" style="font-size:11px;letter-spacing:.06em;text-transform:uppercase;">Reason</div>
                @if(str_contains($detectionEvent->event_type,'Mobile Phone') || $detectionEvent->event_type=='D2')
                    <div>Phone confidence <strong>{{ $detectionEvent->confidence !== null ? number_format($detectionEvent->confidence,2) : '—' }}</strong> ≥ 0.40</div><div class="text-muted" style="font-size:11px;">Size &amp; aspect filters passed · 300px association</div>
                @elseif(str_contains($detectionEvent->event_type,'Tracking Lost') || $detectionEvent->event_type=='S3')
                    @php $abs = ($detectionEvent->ended_at_frame !== null && $detectionEvent->started_at_frame !== null) ? ($detectionEvent->ended_at_frame - $detectionEvent->started_at_frame) : null; @endphp
                    <div>Track absent for <strong>{{ $abs !== null ? $abs : '≥15' }} frames</strong></div><div class="text-muted" style="font-size:11px;">Threshold 15 frames (was 10) · centroid missing</div>
                @elseif(str_contains($detectionEvent->event_type,'Seat Departure') || $detectionEvent->event_type=='B4' || $detectionEvent->event_type=='Leaving Seat')
                    @php $abs = ($detectionEvent->ended_at_frame !== null && $detectionEvent->started_at_frame !== null) ? ($detectionEvent->ended_at_frame - $detectionEvent->started_at_frame) : null; @endphp
                    <div>Track absent for <strong>{{ $abs !== null ? $abs : '≥45' }} frames</strong></div><div class="text-muted" style="font-size:11px;">Threshold 45 frames (was 30) · MVP proxy</div>
                @else
                    <div>Rule score <strong>{{ $detectionEvent->rule_score ?? '—' }}</strong> · confidence {{ $detectionEvent->confidence ?? '—' }}</div><div class="text-muted" style="font-size:11px;">Temporal rule satisfied</div>
                @endif
            </div>
            <div class="col-6 col-md-3"><div class="text-muted" style="font-size:11px;letter-spacing:.06em;text-transform:uppercase;">Trigger Rule</div>
                @if(str_contains($detectionEvent->event_type,'Mobile Phone') || $detectionEvent->event_type=='D2')
                    <div><code>D2</code> YOLO class 67 + conf≥0.40 + w≥30 h≥30 area≥1200 + aspect 0.35–2.20</div>
                @elseif(str_contains($detectionEvent->event_type,'Tracking Lost') || $detectionEvent->event_type=='S3')
                    <div><code>S3</code> 15 ≤ absence &lt; 45 · cooldown 30</div>
                @elseif(str_contains($detectionEvent->event_type,'Seat Departure') || $detectionEvent->event_type=='B4' || $detectionEvent->event_type=='Leaving Seat')
                    <div><code>B4</code> absence ≥45 · cooldown 45 · last known bbox</div>
                @else
                    <div><code>{{ $detectionEvent->event_type }}</code> temporal window 15 · min_supporting 8 · cooldown 45</div>
                @endif
            </div>
        </div>
        <div class="alert alert-warning py-2 mt-3 mb-0" style="font-size:12px;"><i class="bi bi-exclamation-triangle me-1"></i> AI-generated alerts indicate observable events and require human review. Alerts are not proof of academic misconduct. <span class="text-muted">— Reviewer must confirm/dismiss.</span></div>
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
