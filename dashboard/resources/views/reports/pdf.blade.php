<!DOCTYPE html><html><head><meta charset="utf-8"><title>Report {{ $analysisJob->id }}</title></head><body>
<h1>Report for Job {{ $analysisJob->id }}</h1>
<p><strong>AI Disclaimer:</strong> AI-generated alerts indicate observable events that require human review. An alert is not proof of academic misconduct.</p>
<p>Session: {{ $analysisJob->session->name ?? "—" }}</p><p>Job: {{ $analysisJob->id }} Status: {{ $analysisJob->status }}</p><p>Model: {{ $analysisJob->modelVersion->name ?? "" }} {{ $analysisJob->modelVersion->version ?? "" }}</p>
<p>Events: {{ $analysisJob->events->count() }}</p><ul>@foreach($analysisJob->events as $e)<li>{{ $e->event_type }} - {{ $e->review_status }} - Track {{ $e->temporary_track_id }}</li>@endforeach</ul>
<p>Metrics: {{ $analysisJob->metrics ? json_encode($analysisJob->metrics->toArray()) : "—" }}</p>
</body></html>
