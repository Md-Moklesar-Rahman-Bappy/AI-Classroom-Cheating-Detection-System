@extends("layouts.bootstrap")
@section("title","Create Session")
@section("content")
<h2>Create Session</h2>
<form method="POST" action="{{ route("exam-sessions.store") }}">@csrf
<div class="mb-3"><label class="form-label">Name</label><input type="text" name="name" class="form-control" required></div>
<div class="mb-3"><label class="form-label">Room</label><select name="exam_room_id" class="form-select"><option value="">— No room —</option>@foreach($rooms as $r)<option value="{{ $r->id }}">{{ $r->name }}</option>@endforeach</select></div>
<div class="mb-3"><label class="form-label">Status</label><select name="status" class="form-select"><option value="pending">pending</option><option value="active">active</option><option value="completed">completed</option><option value="cancelled">cancelled</option></select></div>
<button class="btn btn-primary">Create</button>
</form>
@endsection
