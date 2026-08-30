@extends("layouts.bootstrap")
@section("title","Upload Video")
@section("content")
<h2>Upload Video Asset</h2>
<form method="POST" action="{{ route("video-assets.store") }}" enctype="multipart/form-data">@csrf
<div class="mb-3"><label class="form-label">Session</label><select name="exam_session_id" class="form-select" required><option value="">Select session</option>@foreach($sessions as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach</select></div>
<div class="mb-3"><label class="form-label">Video File (mp4,avi,mov,mkv, max 500MB)</label><input type="file" name="video" class="form-control" required accept="video/*"></div>
<button class="btn btn-primary">Upload</button>
</form>
@endsection
