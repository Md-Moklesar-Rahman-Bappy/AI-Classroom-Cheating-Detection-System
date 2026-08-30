@extends("layouts.bootstrap")
@section("title","Create Job")
@section("content")
<h2>Create Analysis Job</h2>
<form method="POST" action="{{ route("analysis-jobs.store") }}">@csrf
<div class="mb-3"><label class="form-label">Session</label><select name="exam_session_id" class="form-select" required>@foreach($sessions as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach</select></div>
<div class="mb-3"><label class="form-label">Source Type</label><select name="source_type" class="form-select" id="source_type" required><option value="recorded_video">recorded_video</option><option value="test_source">test_source</option><option value="live_stream">live_stream</option><option value="webcam">webcam</option></select></div>
<div class="mb-3" id="video_asset_group"><label class="form-label">Video Asset <span class="text-danger">*</span></label><select name="video_asset_id" class="form-select" id="video_asset_id">@foreach($videoAssets as $v)<option value="{{ $v->id }}">{{ $v->original_filename }} ({{ $v->stored_filename }}) - {{ $v->session->name ?? "No session" }}</option>@endforeach</select><div class="form-text">Required for recorded_video. Select an uploaded video.</div></div>
<div class="mb-3"><label class="form-label">Model Version</label><select name="model_version_id" class="form-select" required>@foreach($models as $m)<option value="{{ $m->id }}">{{ $m->name }} {{ $m->version }} @if($m->is_active)<span class="badge bg-success">active</span>@endif</option>@endforeach</select></div>
<button class="btn btn-primary">Create Job</button>
</form>
<script>
document.getElementById('source_type').addEventListener('change', function(){
    var group = document.getElementById('video_asset_group');
    if(this.value === 'recorded_video'){
        group.style.display = 'block';
        document.getElementById('video_asset_id').required = true;
    } else {
        group.style.display = 'none';
        document.getElementById('video_asset_id').required = false;
    }
});
</script>
@endsection
