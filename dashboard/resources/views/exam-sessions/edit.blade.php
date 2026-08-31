@extends("layouts.bootstrap")
@section("title","Edit Session")
@section("content")
<div class="mb-4"><h1 class="h4 mb-1" style="font-weight:700;letter-spacing:-.02em">Edit Session</h1><p class="text-muted mb-0" style="font-size:13px">Updating <span class="fw-medium" style="color:var(--color-text)">{{ $examSession->name }}</span> • <code class="text-mono" style="font-size:11px">{{ Str::limit($examSession->id,8) }}</code></p></div>
<div class="card p-4" style="max-width:640px">
<form method="POST" action="{{ route("exam-sessions.update",$examSession) }}" novalidate>
@csrf @method("PUT")
<div class="mb-3">
<label for="name" class="form-label" style="font-size:11px;letter-spacing:.06em;text-transform:uppercase;color:var(--color-text-muted);font-weight:600">Name <span class="text-danger" aria-hidden="true">*</span></label>
<input id="name" type="text" name="name" value="{{ old('name',$examSession->name) }}" class="form-control focus-ring @error('name') is-invalid @enderror" required>
@error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
<div class="mb-3">
<label for="exam_room_id" class="form-label" style="font-size:11px;letter-spacing:.06em;text-transform:uppercase;color:var(--color-text-muted);font-weight:600">Room</label>
<select id="exam_room_id" name="exam_room_id" class="form-select focus-ring">
<option value="">No room assigned</option>
@foreach($rooms as $r)<option value="{{ $r->id }}" @selected(old('exam_room_id',$examSession->exam_room_id)==$r->id)>{{ $r->name }}</option>@endforeach
</select>
</div>
<div class="mb-4">
<label for="status" class="form-label" style="font-size:11px;letter-spacing:.06em;text-transform:uppercase;color:var(--color-text-muted);font-weight:600">Status</label>
<select id="status" name="status" class="form-select focus-ring">
<option value="pending" @selected($examSession->status=="pending")>pending</option>
<option value="active" @selected($examSession->status=="active")>active</option>
<option value="completed" @selected($examSession->status=="completed")>completed</option>
<option value="cancelled" @selected($examSession->status=="cancelled")>cancelled</option>
</select>
</div>
<div class="d-flex gap-2"><button class="btn btn-primary focus-ring">Update</button><a href="{{ route("exam-sessions.show",$examSession) }}" class="btn btn-outline-secondary focus-ring">Cancel</a></div>
</form>
</div>
@endsection
