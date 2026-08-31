@extends("layouts.bootstrap")
@section("title","Job Detail")
@section("content")
<div class="d-flex flex-column flex-md-row justify-content-between align-items-start gap-3 mb-4">
    <div><h1 class="h4 mb-1" style="font-weight:700">Job {{ Str::limit($analysisJob->id,12) }}</h1><p class="text-muted mb-0" style="font-size:13px">Correlation <code class="text-mono">{{ $analysisJob->correlation_id ?? "—" }}</code> • Remote <code class="text-mono">{{ $analysisJob->remote_job_id ?? "No remote job" }}</code></p></div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route("analysis-jobs.index") }}" class="btn btn-outline-secondary btn-sm">Back to jobs</a>
        @if($analysisJob->status=="completed")<a href="{{ route("reports.show",$analysisJob) }}" class="btn btn-success btn-sm">View Report</a>@endif
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-12 col-lg-8">
        <div class="card p-3">
            <div class="d-flex flex-wrap gap-2 mb-3">
                <span class="badge @if($analysisJob->status=="completed") bg-success @elseif($analysisJob->status=="failed") bg-danger @elseif(in_array($analysisJob->status,["processing","queued"])) bg-primary @elseif($analysisJob->status=="cancelled") bg-secondary @else bg-warning text-dark @endif status-badge" style="font-size:13px"><i class="bi @if($analysisJob->status=="completed") bi-check-circle @elseif($analysisJob->status=="failed") bi-x-circle @else bi-hourglass-split @endif me-1" aria-hidden="true"></i>{{ $analysisJob->status }}</span>
                @if($analysisJob->remote_status)<span class="badge bg-dark status-badge">Remote: {{ $analysisJob->remote_status }}</span>@endif
                <span class="badge bg-light text-dark border status-badge" style="font-variant-numeric:tabular-nums">{{ $analysisJob->progress_percent }}% @if($analysisJob->remote_progress) (remote {{ $analysisJob->remote_progress }}%) @endif</span>
                <span class="badge bg-light text-dark border status-badge">{{ $analysisJob->source_type }}</span>
            </div>
            <div class="progress mb-2" style="height:8px"><div class="progress-bar @if($analysisJob->status=="failed") bg-danger @elseif($analysisJob->status=="completed") bg-success @else bg-primary @endif" style="width:{{ $analysisJob->progress_percent }}%"></div></div>
            <div class="d-flex justify-content-between" style="font-size:12px;color:var(--color-text-muted)"><span>Progress from AI service — not invented</span><span style="font-variant-numeric:tabular-nums">{{ $analysisJob->progress_percent }}%</span></div>
            @if($analysisJob->failure_reason)<div class="alert alert-danger mt-3 py-2" role="alert" style="font-size:13px"><i class="bi bi-exclamation-triangle me-1" aria-hidden="true"></i> Failure: {{ $analysisJob->failure_reason }}</div>@endif
            <div class="row g-3 mt-1" style="font-size:13px">
                <div class="col-6"><div class="text-muted" style="font-size:11px;letter-spacing:.06em;text-transform:uppercase">Session</div><div class="fw-medium">{{ $analysisJob->session->name ?? "—" }}</div></div>
                <div class="col-6"><div class="text-muted" style="font-size:11px;letter-spacing:.06em;text-transform:uppercase">Video Asset</div><div class="fw-medium truncate" title="{{ $analysisJob->videoAsset->original_filename ?? "—" }}">{{ $analysisJob->videoAsset->original_filename ?? "—" }}</div></div>
                <div class="col-6"><div class="text-muted" style="font-size:11px;letter-spacing:.06em;text-transform:uppercase">Model</div><div>{{ $analysisJob->modelVersion->name ?? "Not assigned" }} <span class="text-muted" style="font-size:11px">({{ $analysisJob->modelVersion->version ?? "" }})</span></div></div>
                <div class="col-6"><div class="text-muted" style="font-size:11px;letter-spacing:.06em;text-transform:uppercase">Config</div><code class="text-mono" style="font-size:11px;word-break:break-all">{{ json_encode($analysisJob->config) }}</code></div>
                <div class="col-6"><div class="text-muted" style="font-size:11px;letter-spacing:.06em;text-transform:uppercase">Started</div><div>{{ $analysisJob->started_at ?? "Not started" }}</div></div>
                <div class="col-6"><div class="text-muted" style="font-size:11px;letter-spacing:.06em;text-transform:uppercase">Completed</div><div>{{ $analysisJob->completed_at ?? $analysisJob->failed_at ?? "Not completed" }}</div></div>
            </div>
            <div class="d-flex gap-2 flex-wrap mt-3">
                @if(!in_array($analysisJob->status,["completed","failed","cancelled"]))
                    <form method="POST" action="{{ route("analysis-jobs.sync",$analysisJob) }}">@csrf<button class="btn btn-outline-primary btn-sm focus-ring"><i class="bi bi-arrow-repeat me-1" aria-hidden="true"></i> Sync Status</button></form>
                    @can("cancel",$analysisJob)<form method="POST" action="{{ route("analysis-jobs.cancel",$analysisJob) }}">@csrf<button class="btn btn-outline-danger btn-sm focus-ring">Cancel</button></form>@endcan
                @endif
                @if(in_array($analysisJob->status,["failed","cancelled"])) @can("retry",$analysisJob)<form method="POST" action="{{ route("analysis-jobs.retry",$analysisJob) }}">@csrf<button class="btn btn-warning btn-sm focus-ring">Retry — new job</button></form>@endcan @endif
                <a href="{{ route("reports.show",$analysisJob) }}" class="btn btn-success btn-sm focus-ring">View Report</a>
                <a href="{{ route("reports.download",$analysisJob) }}" class="btn btn-outline-success btn-sm focus-ring">Download</a>
            </div>
            <div class="ai-notice mt-3" style="margin-bottom:0"><i class="bi bi-shield-exclamation text-warning" aria-hidden="true"></i><div style="font-size:12px"><strong>Responsible AI:</strong> Metrics and events are observable signals. Human review determines outcome.</div></div>
        </div>
    </div>
    <div class="col-12 col-lg-4">
        <div class="card h-100">
            <div class="card-header bg-white"><h2 class="h6 mb-0" style="font-size:13px;letter-spacing:.06em;text-transform:uppercase"><i class="bi bi-speedometer me-2 text-primary" aria-hidden="true"></i>Metrics</h2></div>
            <div class="card-body" style="font-size:13px">
                @if($analysisJob->metrics)
                    <div class="d-flex justify-content-between mb-2"><span class="text-muted">Source FPS</span><span style="font-variant-numeric:tabular-nums">{{ $analysisJob->metrics->source_fps }}</span></div>
                    <div class="d-flex justify-content-between mb-2"><span class="text-muted">Processing FPS</span><span style="font-variant-numeric:tabular-nums">{{ $analysisJob->metrics->processing_fps }}</span></div>
                    <div class="d-flex justify-content-between mb-2"><span class="text-muted">Latency</span><span style="font-variant-numeric:tabular-nums">{{ $analysisJob->metrics->detection_latency_ms }} ms</span></div>
                    <div class="d-flex justify-content-between mb-2"><span class="text-muted">Memory</span><span style="font-variant-numeric:tabular-nums">{{ $analysisJob->metrics->memory_mb }} MB</span></div>
                    <div class="d-flex justify-content-between"><span class="text-muted">Frames</span><span style="font-variant-numeric:tabular-nums">{{ $analysisJob->metrics->frames_processed ?? "—" }} / {{ $analysisJob->metrics->frames_total ?? "—" }}</span></div>
                @else
                    <div class="empty-state" style="padding:16px"><div class="empty-icon" style="width:36px;height:36px;font-size:16px"><i class="bi bi-hourglass-split" aria-hidden="true"></i></div><p class="text-muted mb-0" style="font-size:13px">Metrics not yet available — polling. Use Sync to refresh.</p></div>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header bg-white d-flex justify-content-between align-items-center"><h2 class="h6 mb-0" style="font-size:13px;letter-spacing:.06em;text-transform:uppercase"><i class="bi bi-activity me-2 text-danger" aria-hidden="true"></i>Events — {{ $analysisJob->events->count() }}</h2><span class="text-muted" style="font-size:12px">Evidence protected — audited access</span></div>
    @if($analysisJob->events->isEmpty())
        <div class="empty-state"><div class="empty-icon"><i class="bi bi-inbox" aria-hidden="true"></i></div><p class="text-muted" style="font-size:13px">No events yet. If processing, Sync to fetch from AI service.</p></div>
    @else
        <div class="table-responsive"><table class="table table-hover align-middle mb-0" style="font-size:13px"><thead><tr><th>Type</th><th>Track</th><th>Review</th><th>Confidence</th><th>Evidence</th><th>Detail</th></tr></thead><tbody>@foreach($analysisJob->events as $e)<tr><td><span class="badge @if($e->event_type=="Mobile Phone Detected"||$e->event_type=="D2") bg-primary @elseif(str_starts_with($e->event_type,"B")) bg-danger @else bg-success @endif status-badge">{{ $e->event_type }}</span></td><td><span class="badge bg-dark status-badge">ID:{{ $e->temporary_track_id }}</span></td><td><span class="badge @if($e->review_status=="pending") bg-warning text-dark @elseif($e->review_status=="confirmed_suspicious") bg-danger @else bg-success @endif status-badge">{{ $e->review_status }}</span></td><td style="font-variant-numeric:tabular-nums">{{ $e->confidence ?? $e->rule_score ?? "—" }}</td><td>@if($e->evidences->isNotEmpty())<a href="{{ route("evidence.show",$e->evidences->first()) }}" class="btn btn-sm btn-outline-primary focus-ring">View</a>@else<span class="text-muted">Not available</span>@endif</td><td><a href="{{ route("detection-events.show",$e) }}" class="btn btn-sm btn-outline-secondary focus-ring">Detail</a></td></tr>@endforeach</tbody></table></div>
    @endif
</div>
@endsection
