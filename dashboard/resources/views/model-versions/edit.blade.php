@extends("layouts.bootstrap")
@section("title","Edit Model")
@section("content")
<h2>Edit {{ $modelVersion->name }}</h2>
<form method="POST" action="{{ route("model-versions.update",$modelVersion) }}">@csrf @method("PUT")
<div class="mb-3"><label class="form-label">Name</label><input type="text" name="name" value="{{ $modelVersion->name }}" class="form-control" required></div>
<div class="mb-3"><label class="form-label">Version</label><input type="text" name="version" value="{{ $modelVersion->version }}" class="form-control" required></div>
<button class="btn btn-primary">Update</button>
</form>
@endsection
