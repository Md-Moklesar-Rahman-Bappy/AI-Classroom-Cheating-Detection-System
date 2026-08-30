@extends("layouts.bootstrap")
@section("title","Create Room")
@section("content")
<h2>Create Exam Room</h2>
<form method="POST" action="{{ route("exam-rooms.store") }}">@csrf
<div class="mb-3"><label class="form-label">Name</label><input type="text" name="name" class="form-control" required maxlength="150"></div>
<div class="mb-3"><label class="form-label">Building</label><input type="text" name="building" class="form-control" maxlength="150"></div>
<div class="mb-3"><label class="form-label">Capacity</label><input type="number" name="capacity" class="form-control" min="1"></div>
<div class="mb-3"><label class="form-label">Camera Notes</label><textarea name="camera_position_notes" class="form-control"></textarea></div>
<button class="btn btn-primary">Create</button> <a href="{{ route("exam-rooms.index") }}" class="btn btn-secondary">Cancel</a>
</form>
@endsection
