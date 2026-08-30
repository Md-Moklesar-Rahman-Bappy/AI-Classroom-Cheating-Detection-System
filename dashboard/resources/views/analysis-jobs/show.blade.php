@extends("layouts.bootstrap")
@section("title","Job Detail")
@section("content")
<h2>Job {{ Str::limit($analysisJob->id,8) }}</h2>
<div class="card p-3"><p>Status: <span class="badge bg-info">{{ $analysisJob->status }}</span> <span class="badge bg-secondary">{{ $analysisJob->progress_percent }}%</span></p><p>Config: <code>{{ json_encode($analysisJob->config) }}</code></p><p>Failure: {{ $analysisJob->failure_reason ?? "—" }}</p><p>Metrics: <a href="{{ route("metrics.index") }}">View metrics</a></p></div>
<h4 class="mt-3">Events</h4><p><a href="{{ route("detection-events.index") }}?job={{ $analysisJob->id }}">View events for this job</a></p>
@endsection
