@extends("layouts.bootstrap")
@section("title","Job Report")
@section("content")
<h2>Report for Job {{ $analysisJob->id }}</h2>
<div class="alert alert-warning">AI-generated alerts indicate observable events that require human review. An alert is not proof of academic misconduct.</div>
<div class="card p-3 mb-3">
    <h5>Exam Session</h5><p>{{ $analysisJob->session->name ?? "Not assigned" }} (Room: {{ $analysisJob->session->room->name ?? "No room assigned" }})</p>
    <h5>Source Mode</h5><p>{{ $analysisJob->source_type }} @if($analysisJob->videoAsset) - Video: {{ $analysisJob->videoAsset->original_filename }} @endif</p>
    <h5>Analysis Job</h5><p>ID: {{ $analysisJob->id }}<br>Status: <span class="badge bg-info">{{ $analysisJob->status }}</span> Progress: {{ $analysisJob->progress_percent }}%<br>Remote: {{ $analysisJob->remote_job_id ?? "No remote job" }} ({{ $analysisJob->remote_status ?? "Not available" }})<br>Started: {{ $analysisJob->started_at ?? "Not started" }} Completed: {{ $analysisJob->completed_at ?? "Not completed" }}<br>Failure: {{ $analysisJob->failure_reason ?? "Not available" }}</p>
    <h5>Model Version</h5><p>{{ $analysisJob->modelVersion->name ?? "Not assigned" }} {{ $analysisJob->modelVersion->version ?? "" }} ({{ $analysisJob->modelVersion->license ?? "" }}) Checksum: {{ $analysisJob->modelVersion->checksum_sha256 ?? "Not available" }}</p>
    <h5>Configuration</h5><pre>{{ json_encode($analysisJob->config, JSON_PRETTY_PRINT) }}</pre><p>Remote Output: <pre>{{ json_encode($analysisJob->remote_output_metadata, JSON_PRETTY_PRINT) }}</pre></p>
</div>
<div class="card p-3 mb-3">
    <h5>Events ({{ $analysisJob->events->count() }})</h5>
    @if($analysisJob->events->isEmpty())<p class="text-muted">No events.</p>
    @else<table class="table"><thead><tr><th>Type</th><th>Track</th><th>Review</th><th>Confidence</th></tr></thead><tbody>@foreach($analysisJob->events as $e)<tr><td>{{ $e->event_type }}</td><td>{{ $e->temporary_track_id }}</td><td><span class="badge bg-warning text-dark">{{ $e->review_status }}</span></td><td>{{ $e->confidence ?? $e->rule_score ?? "Not available" }}</td></tr>@endforeach</tbody></table>@endif
</div>
<div class="card p-3 mb-3">
    <h5>Human Review State</h5>
    @foreach($analysisJob->events as $e)<p>{{ $e->event_type }}: {{ $e->review_status }} @if($e->reviewer_note) - Note: {{ $e->reviewer_note }} @endif</p>@endforeach
</div>
<div class="card p-3 mb-3">
    <h5>Metrics</h5>
    @if($analysisJob->metrics)<p>Source FPS: {{ $analysisJob->metrics->source_fps }} Processing FPS: {{ $analysisJob->metrics->processing_fps }} Latency: {{ $analysisJob->metrics->detection_latency_ms }}ms</p>
    @else<p class="text-muted">No metrics yet.</p>@endif
</div>
<div class="card p-3 mb-3">
    <h5>Disclaimer</h5><p class="text-muted">This report contains AI-generated observations that require human review. An alert is not proof of academic misconduct. All decisions were made by authorized human reviewers and are audited.</p>
</div>
<a href="{{ route("reports.download",$analysisJob) }}" class="btn btn-primary">Download Report</a> <a href="{{ route("analysis-jobs.show",$analysisJob) }}" class="btn btn-secondary">Back to Job</a>
@endsection
