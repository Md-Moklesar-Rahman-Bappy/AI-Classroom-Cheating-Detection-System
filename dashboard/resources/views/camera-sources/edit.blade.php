@extends("layouts.bootstrap")
@section("title","Edit Camera")
@section("content")
<h2>Edit {{ $cameraSource->name }}</h2>
<form method="POST" action="{{ route("camera-sources.update",$cameraSource) }}">@csrf @method("PUT")
<div class="mb-3"><label class="form-label">Name</label><input type="text" name="name" value="{{ $cameraSource->name }}" class="form-control" required></div>
<div class="mb-3"><label class="form-label">Type</label><select name="source_type" class="form-select"><option value="webcam" @selected($cameraSource->source_type=="webcam")>webcam</option><option value="rtsp" @selected($cameraSource->source_type=="rtsp")>rtsp</option></select></div>
<div class="mb-3"><label class="form-label">Identifier</label><input type="text" name="identifier" value="{{ $cameraSource->identifier }}" class="form-control" required></div>
<button class="btn btn-primary">Update</button>
</form>
@endsection
