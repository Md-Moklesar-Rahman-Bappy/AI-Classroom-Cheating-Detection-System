@extends("layouts.bootstrap")
@section("title","Job Detail")
@section("content")
<h2>Job {{ $analysisJob->id }}</h2>
<div class="card p-3 mb-3">
    <p>Status: <span class="badge @if($analysisJob->status=="completed") bg-success @elseif($analysisJob->status=="failed") bg-danger @elseif($analysisJob->status=="processing"||$analysisJob->status=="queued") bg-primary @elseif($analysisJob->status=="cancelled") bg-secondary @else bg-warning text-dark @endif">{{ $analysisJob->status }}</span>
    @if($analysisJob->remote_status) <span class="badge bg-dark">Remote: {{ $analysisJob->remote_status }}</span> @endif
    <span class="badge bg-secondary">{{ $analysisJob->progress_percent }}% @if($analysisJob->remote_progress) (remote {{ $analysisJob->remote_progress }}%) @endif</span></p>
    <div class="progress mb-2" style="height: 20px;"><div class="progress-bar @if($analysisJob->status=="failed") bg-danger @elseif($analysisJob->status=="completed") bg-success @else bg-primary @endif" style="width: {{ $analysisJob->progress_percent }}%">{{ $analysisJob->progress_percent }}%</div></div>
    <p>Processed: {{ $analysisJob->progress_percent }}% where available | Started: {{ $analysisJob->started_at ?? "Not started" }} | Completed: {{ $analysisJob->completed_at ?? $analysisJob->failed_at ?? "Not completed" }}</p>
    @if($analysisJob->failure_reason)<div class="alert alert-danger">Failure: {{ $analysisJob->failure_reason }}</div>@endif
    <p>Config: <code>{{ json_encode($analysisJob->config) }}</code> Model: {{ $analysisJob->modelVersion->name ?? "Not assigned" }} ({{ $analysisJob->remote_output_metadata["checksum"] ?? $analysisJob->modelVersion->checksum_sha256 ?? "Not available" }})</p>
    <p>Correlation ID: <code>{{ $analysisJob->correlation_id ?? "Not available" }}</code> Remote ID: <code>{{ $analysisJob->remote_job_id ?? "No remote job" }}</code></p>
    <div class="d-flex gap-2 flex-wrap">
        @if(!in_array($analysisJob->status,["completed","failed","cancelled"]))
            <form method="POST" action="{{ route("analysis-jobs.sync",$analysisJob) }}">@csrf<button class="btn btn-outline-primary btn-sm">Sync Status</button></form>
            <form method="POST" action="{{ route("analysis-jobs.cancel",$analysisJob) }}">@csrf<button class="btn btn-outline-danger btn-sm">Cancel</button></form>
        @endif
        @if(in_array($analysisJob->status,["failed","cancelled"]))
            <form method="POST" action="{{ route("analysis-jobs.retry",$analysisJob) }}">@csrf<button class="btn btn-warning btn-sm">Retry</button></form>
        @endif
        <a href="{{ route("reports.show",$analysisJob) }}" class="btn btn-success btn-sm">View Report</a>
        <a href="{{ route("reports.download",$analysisJob) }}" class="btn btn-outline-success btn-sm">Download Report</a>
    </div>
</div>
<div class="card p-3 mb-3">
    <h5>Metrics</h5>
    @if($analysisJob->metrics)<p>Source FPS: {{ $analysisJob->metrics->source_fps }} | Processing FPS: {{ $analysisJob->metrics->processing_fps }} | Latency: {{ $analysisJob->metrics->detection_latency_ms }}ms | Memory: {{ $analysisJob->metrics->memory_mb }} MB</p>
    @else<p class="text-muted">Metrics not yet available (polling).</p>@endif
</div>
<h4>Events ({{ $analysisJob->events->count() }})</h4>
@if($analysisJob->events->isEmpty())<p class="text-muted">No events yet. If job is processing, sync to fetch.</p>
@else<div class="table-responsive"><table class="table"><thead><tr><th>Type</th><th>Track</th><th>Review</th><th>Evidence</th></tr></thead><tbody>@foreach($analysisJob->events as $e)<tr><td><span class="badge bg-info">{{ $e->event_type }}</span></td><td>{{ $e->temporary_track_id }}</td><td><span class="badge bg-warning text-dark">{{ $e->review_status }}</span></td><td>@if($e->evidences->isNotEmpty())<a href="{{ route("evidence.show",$e->evidences->first()) }}" class="btn btn-sm btn-outline-primary">View</a>@else<span class="text-muted">Not available</span>@endif</td></tr>@endforeach</tbody></table></div>@endif
<p class="text-muted small">Progress is from AI service; not invented. Evidence is protected and audited.</p>
@endsection
