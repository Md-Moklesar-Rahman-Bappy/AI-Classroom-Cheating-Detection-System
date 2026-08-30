@extends("layouts.bootstrap")
@section("title","Add Model")
@section("content")
<h2>Add Model Version</h2>
<form method="POST" action="{{ route("model-versions.store") }}">@csrf
<div class="mb-3"><label class="form-label">Name</label><input type="text" name="name" class="form-control" required value="yolo11n.pt"></div>
<div class="mb-3"><label class="form-label">Version</label><input type="text" name="version" class="form-control" required value="v1"></div>
<div class="mb-3"><label class="form-label">Checksum SHA256 (64)</label><input type="text" name="checksum_sha256" class="form-control" required minlength="64" maxlength="64"></div>
<div class="mb-3"><label class="form-label">License</label><input type="text" name="license" class="form-control" required value="AGPL-3.0"></div>
<button class="btn btn-primary">Create</button>
</form>
@endsection
