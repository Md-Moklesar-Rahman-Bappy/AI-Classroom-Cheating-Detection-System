<!DOCTYPE html><html><head><meta charset="utf-8"><title>Analysis Report</title></head><body>
<h1>Analysis Report</h1>
<p><strong>AI Disclaimer:</strong> AI-generated alerts indicate observable events that require human review. An alert is not proof of academic misconduct.</p>
<p>Session: {{ $analysisJob->session->name ?? "Not assigned" }}</p><p>Status: {{ $analysisJob->status }}</p><p>Model: {{ $analysisJob->modelVersion->name ?? "Not assigned" }} {{ $analysisJob->modelVersion->version ?? "" }}</p>
<p>Events: {{ $analysisJob->events->count() }}</p><ul>@foreach($analysisJob->events as $e)<li>{{ $e->event_type }} - {{ $e->review_status }} - Track {{ $e->temporary_track_id }}</li>@endforeach</ul>
<p>Metrics: {{ $analysisJob->metrics ? json_encode($analysisJob->metrics->toArray()) : "Not available" }}</p>
</body></html>
