@extends("layouts.bootstrap")
@section("title","Edit Room")
@section("content")
<div class="mb-4"><h1 class="h4 mb-1" style="font-weight:700;letter-spacing:-.02em">Edit Room</h1><p class="text-muted mb-0" style="font-size:13px">Updating <span class="fw-medium" style="color:var(--color-text)">{{ $examRoom->name }}</span> • <code class="text-mono" style="font-size:11px">{{ Str::limit($examRoom->id,8) }}</code></p></div>
<div class="card p-4" style="max-width:640px">
<form method="POST" action="{{ route("exam-rooms.update",$examRoom) }}" novalidate>
@csrf @method("PUT")
<div class="mb-3">
<label for="name" class="form-label" style="font-size:11px;letter-spacing:.06em;text-transform:uppercase;color:var(--color-text-muted);font-weight:600">Name <span class="text-danger" aria-hidden="true">*</span></label>
<input id="name" type="text" name="name" value="{{ old('name',$examRoom->name) }}" class="form-control focus-ring @error('name') is-invalid @enderror" required>
@error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
<div class="mb-3">
<label for="building" class="form-label" style="font-size:11px;letter-spacing:.06em;text-transform:uppercase;color:var(--color-text-muted);font-weight:600">Building</label>
<input id="building" type="text" name="building" value="{{ old('building',$examRoom->building) }}" class="form-control focus-ring @error('building') is-invalid @enderror">
@error('building')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
<div class="mb-3">
<label for="capacity" class="form-label" style="font-size:11px;letter-spacing:.06em;text-transform:uppercase;color:var(--color-text-muted);font-weight:600">Capacity</label>
<input id="capacity" type="number" name="capacity" value="{{ old('capacity',$examRoom->capacity) }}" class="form-control focus-ring @error('capacity') is-invalid @enderror" min="1">
@error('capacity')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
<div class="mb-4">
<label for="camera_position_notes" class="form-label" style="font-size:11px;letter-spacing:.06em;text-transform:uppercase;color:var(--color-text-muted);font-weight:600">Camera Notes</label>
<textarea id="camera_position_notes" name="camera_position_notes" rows="3" class="form-control focus-ring">{{ old('camera_position_notes',$examRoom->camera_position_notes) }}</textarea>
</div>
<div class="d-flex gap-2">
<button class="btn btn-primary focus-ring">Update</button>
<a href="{{ route("exam-rooms.show",$examRoom) }}" class="btn btn-outline-secondary focus-ring">Cancel</a>
</div>
</form>
</div>
@endsection
