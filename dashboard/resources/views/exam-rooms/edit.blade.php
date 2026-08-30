@extends("layouts.bootstrap")
@section("title","Edit Room")
@section("content")
<h2>Edit {{ $examRoom->name }}</h2>
<form method="POST" action="{{ route("exam-rooms.update",$examRoom) }}">@csrf @method("PUT")
<div class="mb-3"><label class="form-label">Name</label><input type="text" name="name" value="{{ $examRoom->name }}" class="form-control" required></div>
<div class="mb-3"><label class="form-label">Building</label><input type="text" name="building" value="{{ $examRoom->building }}" class="form-control"></div>
<div class="mb-3"><label class="form-label">Capacity</label><input type="number" name="capacity" value="{{ $examRoom->capacity }}" class="form-control"></div>
<button class="btn btn-primary">Update</button>
</form>
@endsection
