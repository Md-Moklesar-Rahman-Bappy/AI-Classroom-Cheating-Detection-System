@extends("layouts.bootstrap")
@section("title","Create Job")
@section("content")
<h2>Create Analysis Job</h2>
<form method="POST" action="{{ route("analysis-jobs.store") }}">@csrf
<div class="mb-3"><label class="form-label">Session</label><select name="exam_session_id" class="form-select" required>@foreach($sessions as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach</select></div>
<div class="mb-3"><label class="form-label">Source Type</label><select name="source_type" class="form-select"><option value="recorded_video">recorded_video</option><option value="test_source">test_source</option></select></div>
<div class="mb-3"><label class="form-label">Model Version</label><select name="model_version_id" class="form-select" required>@foreach($models as $m)<option value="{{ $m->id }}">{{ $m->name }} {{ $m->version }}</option>@endforeach</select></div>
<button class="btn btn-primary">Create Job</button>
</form>
@endsection
