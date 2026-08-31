@extends("layouts.bootstrap")
@section("title","Job Report")
@section("content")
<div class="d-flex flex-column flex-md-row justify-content-between align-items-start gap-3 mb-4">
<div><h1 class="h4 mb-1" style="font-weight:700;letter-spacing:-.02em">Report — Job {{ Str::limit($analysisJob->id,12) }}</h1><p class="text-muted mb-0" style="font-size:13px">Correlation <code class="text-mono" style="font-size:11px">{{ $analysisJob->correlation_id ?? "—" }}</code> • Remote <code class="text-mono" style="font-size:11px">{{ $analysisJob->remote_job_id ?? "No remote job" }}</code></p></div>
<div class="d-flex gap-2 flex-wrap"><a href="{{ route("analysis-jobs.show",$analysisJob) }}" class="btn btn-outline-secondary btn-sm focus-ring">Back to Job</a><a href="{{ route("reports.download",$analysisJob) }}" class="btn btn-success btn-sm focus-ring"><i class="bi bi-download me-1" aria-hidden="true"></i> Download</a></div>
</div>

<div class="alert alert-warning d-flex gap-2 py-2" role="note" style="font-size:13px"><i class="bi bi-shield-exclamation flex-shrink-0" aria-hidden="true"></i><div><strong>AI Notice:</strong> AI-generated alerts indicate observable events that require human review. An alert is not proof of academic misconduct. Final decisions remain with authorized human reviewers.</div></div>

<div class="row g-4 mb-4">
<div class="col-12 col-lg-8">
<div class="card">
<div class="card-header bg-white"><h2 class="h6 mb-0" style="font-size:11px;letter-spacing:.06em;text-transform:uppercase"><i class="bi bi-file-text me-2 text-primary" aria-hidden="true"></i>Job Summary</h2></div>
<div class="card-body" style="font-size:13px">
<div class="row g-3">
<div class="col-12 col-md-6"><div class="text-muted" style="font-size:11px;letter-spacing:.06em;text-transform:uppercase">Exam Session</div><div class="fw-medium">{{ $analysisJob->session->name ?? "Not assigned" }}</div><div class="text-muted" style="font-size:11px">Room: {{ $analysisJob->session->room->name ?? "No room assigned" }}</div></div>
<div class="col-12 col-md-6"><div class="text-muted" style="font-size:11px;letter-spacing:.06em;text-transform:uppercase">Source</div><div><span class="badge bg-light text-dark border status-badge">{{ $analysisJob->source_type }}</span> @if($analysisJob->videoAsset)<span class="text-muted truncate d-inline-block" style="max-width:160px;vertical-align:bottom" title="{{ $analysisJob->videoAsset->original_filename }}">{{ $analysisJob->videoAsset->original_filename }}</span>@endif</div></div>
<div class="col-6"><div class="text-muted" style="font-size:11px;letter-spacing:.06em;text-transform:uppercase">Status</div><div><span class="badge @if($analysisJob->status=="completed") bg-success @elseif($analysisJob->status=="failed") bg-danger @else bg-warning text-dark @endif status-badge">{{ $analysisJob->status }}</span> <span class="text-muted" style="font-variant-numeric:tabular-nums">{{ $analysisJob->progress_percent }}%</span></div></div>
<div class="col-6"><div class="text-muted" style="font-size:11px;letter-spacing:.06em;text-transform:uppercase">Remote</div><div><code class="text-mono" style="font-size:11px">{{ $analysisJob->remote_job_id ?? "No remote job" }}</code> <span class="badge bg-light text-dark border status-badge">{{ $analysisJob->remote_status ?? "Not available" }}</span></div></div>
<div class="col-6"><div class="text-muted" style="font-size:11px;letter-spacing:.06em;text-transform:uppercase">Started</div><div>{{ $analysisJob->started_at ?? "Not started" }}</div></div>
<div class="col-6"><div class="text-muted" style="font-size:11px;letter-spacing:.06em;text-transform:uppercase">Completed</div><div>{{ $analysisJob->completed_at ?? $analysisJob->failed_at ?? "Not completed" }}</div></div>
@if($analysisJob->failure_reason)<div class="col-12"><div class="alert alert-danger py-2 mb-0" style="font-size:12px"><i class="bi bi-exclamation-triangle me-1" aria-hidden="true"></i> {{ $analysisJob->failure_reason }}</div></div>@endif
<div class="col-12"><div class="text-muted" style="font-size:11px;letter-spacing:.06em;text-transform:uppercase">Model</div><div>{{ $analysisJob->modelVersion->name ?? "Not assigned" }} <span class="text-muted">{{ $analysisJob->modelVersion->version ?? "" }}</span> <span class="badge bg-dark status-badge">{{ $analysisJob->modelVersion->license ?? "—" }}</span> <code class="text-mono ms-2" style="font-size:11px" title="{{ $analysisJob->modelVersion->checksum_sha256 ?? "" }}">{{ Str::limit($analysisJob->modelVersion->checksum_sha256 ?? "Not available",16) }}</code> @if(!empty($analysisJob->modelVersion->checksum_sha256))<button class="btn btn-sm btn-link p-0 ms-1" onclick="navigator.clipboard.writeText('{{ $analysisJob->modelVersion->checksum_sha256 }}')" aria-label="Copy checksum"><i class="bi bi-copy" style="font-size:11px" aria-hidden="true"></i></button>@endif</div></div>
<div class="col-12"><div class="text-muted" style="font-size:11px;letter-spacing:.06em;text-transform:uppercase">Configuration</div><pre class="bg-light border rounded p-2 mb-0 text-mono" style="font-size:11px;white-space:pre-wrap;word-break:break-all">{{ json_encode($analysisJob->config, JSON_PRETTY_PRINT) }}</pre></div>
<div class="col-12"><div class="text-muted" style="font-size:11px;letter-spacing:.06em;text-transform:uppercase">Remote Output Metadata</div><pre class="bg-light border rounded p-2 mb-0 text-mono" style="font-size:11px;white-space:pre-wrap;word-break:break-all">{{ json_encode($analysisJob->remote_output_metadata, JSON_PRETTY_PRINT) }}</pre></div>
</div>
</div>
</div>
</div>
<div class="col-12 col-lg-4">
<div class="card h-100">
<div class="card-header bg-white"><h2 class="h6 mb-0" style="font-size:11px;letter-spacing:.06em;text-transform:uppercase"><i class="bi bi-speedometer me-2 text-success" aria-hidden="true"></i>Metrics</h2></div>
<div class="card-body" style="font-size:13px">
@if($analysisJob->metrics)
<div class="d-flex justify-content-between mb-2"><span class="text-muted">Source FPS</span><span style="font-variant-numeric:tabular-nums">{{ $analysisJob->metrics->source_fps }}</span></div>
<div class="d-flex justify-content-between mb-2"><span class="text-muted">Processing FPS</span><span style="font-variant-numeric:tabular-nums">{{ $analysisJob->metrics->processing_fps }}</span></div>
<div class="d-flex justify-content-between mb-2"><span class="text-muted">Latency</span><span style="font-variant-numeric:tabular-nums">{{ $analysisJob->metrics->detection_latency_ms }} ms</span></div>
<div class="d-flex justify-content-between mb-2"><span class="text-muted">Memory</span><span style="font-variant-numeric:tabular-nums">{{ $analysisJob->metrics->memory_mb }} MB</span></div>
<div class="d-flex justify-content-between"><span class="text-muted">Frames</span><span style="font-variant-numeric:tabular-nums">{{ $analysisJob->metrics->frames_processed ?? "—" }} / {{ $analysisJob->metrics->frames_total ?? "—" }}</span></div>
@else
<div class="empty-state" style="padding:16px"><div class="empty-icon" style="width:36px;height:36px;font-size:16px"><i class="bi bi-hourglass-split" aria-hidden="true"></i></div><p class="text-muted mb-0" style="font-size:13px">No metrics yet.</p></div>
@endif
</div>
</div>
</div>
</div>

<div class="card mb-4">
<div class="card-header bg-white d-flex justify-content-between align-items-center"><h2 class="h6 mb-0" style="font-size:11px;letter-spacing:.06em;text-transform:uppercase"><i class="bi bi-activity me-2 text-danger" aria-hidden="true"></i>Events — {{ $analysisJob->events->count() }}</h2><span class="text-muted" style="font-size:11px">Human review determines outcome</span></div>
@if($analysisJob->events->isEmpty())
<div class="empty-state"><div class="empty-icon"><i class="bi bi-inbox" aria-hidden="true"></i></div><p class="text-muted" style="font-size:13px">No events.</p></div>
@else
<div class="table-responsive d-none d-md-block">
<table class="table table-hover align-middle mb-0" style="font-size:13px">
<caption class="visually-hidden">Detection events for this job</caption>
<thead><tr><th>Type</th><th>Track</th><th>Review</th><th>Confidence</th><th>Note</th></tr></thead>
<tbody>
@foreach($analysisJob->events as $e)
<tr>
<td><span class="badge @if($e->event_type=="Mobile Phone Detected") bg-primary @elseif(str_starts_with($e->event_type,"B")) bg-danger @else bg-success @endif status-badge">{{ $e->event_type }}</span></td>
<td><span class="badge bg-dark status-badge">ID:{{ $e->temporary_track_id }}</span></td>
<td><span class="badge @if($e->review_status=="pending") bg-warning text-dark @elseif($e->review_status=="confirmed_suspicious") bg-danger @else bg-success @endif status-badge"><i class="bi @if($e->review_status=="pending") bi-hourglass @else bi-check-circle @endif me-1" aria-hidden="true"></i>{{ $e->review_status }}</span></td>
<td style="font-variant-numeric:tabular-nums">{{ $e->confidence ?? $e->rule_score ?? "Not available" }}</td>
<td class="text-muted" style="font-size:12px;max-width:200px"><span class="truncate d-inline-block" style="max-width:200px" title="{{ $e->reviewer_note }}">{{ $e->reviewer_note ?? "—" }}</span></td>
</tr>
@endforeach
</tbody>
</table>
</div>
<div class="d-md-none p-2">
@foreach($analysisJob->events as $e)
<div class="card p-3 mb-2">
<div class="d-flex justify-content-between gap-2"><span class="badge bg-primary status-badge">{{ $e->event_type }}</span><span class="badge @if($e->review_status=="pending") bg-warning text-dark @else bg-success @endif status-badge">{{ $e->review_status }}</span></div>
<div class="text-muted mt-2" style="font-size:12px">Track ID:{{ $e->temporary_track_id }} • {{ $e->confidence ?? $e->rule_score ?? "—" }}</div>
@if($e->reviewer_note)<div class="text-muted" style="font-size:12px">Note: {{ $e->reviewer_note }}</div>@endif
</div>
@endforeach
</div>
@endif
</div>

<div class="card border-warning">
<div class="card-header bg-white"><h2 class="h6 mb-0" style="font-size:11px;letter-spacing:.06em;text-transform:uppercase"><i class="bi bi-shield-exclamation me-2 text-warning" aria-hidden="true"></i>Disclaimer</h2></div>
<div class="card-body" style="font-size:13px;color:var(--color-text-muted)"><p class="mb-0">This report contains AI-generated observations that require human review. An alert is not proof of academic misconduct. All decisions were made by authorized human reviewers and are audited. Evidence is retained under institutional policy.</p></div>
</div>
@endsection
