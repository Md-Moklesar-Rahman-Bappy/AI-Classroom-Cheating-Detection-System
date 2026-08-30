@extends("layouts.bootstrap")
@section("title","Edit Session")
@section("content")
<h2>Edit {{ $examSession->name }}</h2>
<form method="POST" action="{{ route("exam-sessions.update",$examSession) }}">@csrf @method("PUT")
<div class="mb-3"><label class="form-label">Name</label><input type="text" name="name" value="{{ $examSession->name }}" class="form-control" required></div>
<div class="mb-3"><label class="form-label">Status</label><select name="status" class="form-select"><option value="pending" @selected($examSession->status=="pending")>pending</option><option value="active" @selected($examSession->status=="active")>active</option><option value="completed" @selected($examSession->status=="completed")>completed</option><option value="cancelled" @selected($examSession->status=="cancelled")>cancelled</option></select></div>
<button class="btn btn-primary">Update</button>
</form>
@endsection
