@extends("layouts.bootstrap")
@section("title","Create Session")
@section("content")
<div class="mb-4"><h1 class="h4 mb-1" style="font-weight:700;letter-spacing:-.02em">Create Session</h1><p class="text-muted mb-0" style="font-size:13px">Link to a room and set initial status.</p></div>
<div class="card p-4" style="max-width:640px">
<form method="POST" action="{{ route("exam-sessions.store") }}" novalidate>
@csrf
<div class="mb-3">
<label for="name" class="form-label" style="font-size:11px;letter-spacing:.06em;text-transform:uppercase;color:var(--color-text-muted);font-weight:600">Name <span class="text-danger" aria-hidden="true">*</span></label>
<input id="name" type="text" name="name" value="{{ old('name') }}" class="form-control focus-ring @error('name') is-invalid @enderror" required placeholder="e.g., Final Exam — 2026-06-15">
@error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
<div class="mb-3">
<label for="exam_room_id" class="form-label" style="font-size:11px;letter-spacing:.06em;text-transform:uppercase;color:var(--color-text-muted);font-weight:600">Room</label>
<select id="exam_room_id" name="exam_room_id" class="form-select focus-ring @error('exam_room_id') is-invalid @enderror">
<option value="">No room assigned</option>
@foreach($rooms as $r)<option value="{{ $r->id }}" @selected(old('exam_room_id')==$r->id)>{{ $r->name }} @if($r->building) — {{ $r->building }} @endif</option>@endforeach
</select>
@error('exam_room_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
<div class="mb-4">
<label for="status" class="form-label" style="font-size:11px;letter-spacing:.06em;text-transform:uppercase;color:var(--color-text-muted);font-weight:600">Status <span class="text-danger" aria-hidden="true">*</span></label>
<select id="status" name="status" class="form-select focus-ring @error('status') is-invalid @enderror" required>
<option value="pending" @selected(old('status')=='pending')>pending</option>
<option value="active" @selected(old('status')=='active')>active</option>
<option value="completed" @selected(old('status')=='completed')>completed</option>
<option value="cancelled" @selected(old('status')=='cancelled')>cancelled</option>
</select>
@error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
<div class="d-flex gap-2"><button class="btn btn-primary focus-ring">Create</button><a href="{{ route("exam-sessions.index") }}" class="btn btn-outline-secondary focus-ring">Cancel</a></div>
</form>
</div>
@endsection
