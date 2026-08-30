@extends("layouts.bootstrap")
@section("title","Edit Job")
@section("content")
<h2>Edit Analysis Job #{{ $analysisJob->id }}</h2>
<div class="alert alert-info"><i class="bi bi-info-circle me-1"></i> Only pending jobs can be edited. Status: <span class="badge bg-warning text-dark">{{ $analysisJob->status }}</span></div>
<form method="POST" action="{{ route("analysis-jobs.update",$analysisJob) }}">@csrf @method("PUT")
<div class="mb-3"><label class="form-label">Session</label><select name="exam_session_id" class="form-select" required>@foreach($sessions as $s)<option value="{{ $s->id }}" @selected($analysisJob->exam_session_id==$s->id)>{{ $s->name }}</option>@endforeach</select></div>
<div class="mb-3"><label class="form-label">Source Type</label><select name="source_type" class="form-select" required><option value="recorded_video" @selected($analysisJob->source_type=="recorded_video")>recorded_video</option><option value="test_source" @selected($analysisJob->source_type=="test_source")>test_source</option><option value="live_stream" @selected($analysisJob->source_type=="live_stream")>live_stream</option><option value="webcam" @selected($analysisJob->source_type=="webcam")>webcam</option></select></div>
<div class="mb-3"><label class="form-label">Video Asset</label><select name="video_asset_id" class="form-select"><option value="">— None (test/live) —</option>@foreach($videoAssets as $v)<option value="{{ $v->id }}" @selected($analysisJob->video_asset_id==$v->id)>{{ $v->original_filename }}</option>@endforeach</select></div>
<div class="mb-3"><label class="form-label">Model Version</label><select name="model_version_id" class="form-select" required>@foreach($models as $m)<option value="{{ $m->id }}" @selected($analysisJob->model_version_id==$m->id)>{{ $m->name }} {{ $m->version }}</option>@endforeach</select></div>
<button class="btn btn-primary">Update</button> <a href="{{ route("analysis-jobs.show",$analysisJob) }}" class="btn btn-secondary">Cancel</a>
</form>
@endsection
