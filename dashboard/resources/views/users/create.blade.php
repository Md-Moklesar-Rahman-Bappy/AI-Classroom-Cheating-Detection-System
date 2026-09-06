@extends("layouts.bootstrap")
@section("title","Create User")
@section("content")
<div class="d-flex justify-content-between align-items-center mb-4">
<div><h1 class="h5 mb-1" style="font-weight:700">Create User</h1><p class="text-muted mb-0" style="font-size:13px">Add a new account and assign a role</p></div>
<a href="{{ route("users.index") }}" class="btn btn-outline-secondary btn-sm focus-ring"><i class="bi bi-arrow-left me-1"></i> Back</a>
</div>
<form method="POST" action="{{ route("users.store") }}" class="card p-4" style="max-width:640px">@csrf
<div class="mb-3"><label class="form-label" for="name">Name <span class="text-danger">*</span></label><input type="text" id="name" name="name" value="{{ old("name") }}" class="form-control @error("name") is-invalid @enderror" required><@error("name")<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
<div class="mb-3"><label class="form-label" for="email">Email <span class="text-danger">*</span></label><input type="email" id="email" name="email" value="{{ old("email") }}" class="form-control @error("email") is-invalid @enderror" required><@error("email")<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
<div class="mb-3"><label class="form-label" for="password">Password <span class="text-danger">*</span> <span class="text-muted" style="font-size:11px">(min 8, letters+numbers+symbols)</span></label><input type="password" id="password" name="password" class="form-control @error("password") is-invalid @enderror" required><@error("password")<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
<div class="mb-3"><label class="form-label" for="password_confirmation">Confirm Password <span class="text-danger">*</span></label><input type="password" id="password_confirmation" name="password_confirmation" class="form-control" required></div>
<div class="mb-3">
<label class="form-label" for="roles">Role <span class="text-danger">*</span></label>
<select name="roles[]" id="roles" class="form-select @error("roles") is-invalid @enderror" required>
<option value="" disabled {{ old("roles") ? "" : "selected" }}>Select a role</option>
@foreach($roles as $r)
@php $label = match($r->name){ "system_admin"=>"System Administrator","exam_admin"=>"Exam Administrator","invigilator"=>"Invigilator","reviewer"=>"Reviewer","auditor"=>"Auditor", default=> ucwords(str_replace("_"," ",$r->name)) }; $desc = match($r->name){ "system_admin"=>"Full system control","exam_admin"=>"Exam/session management","invigilator"=>"Monitoring only","reviewer"=>"Evidence review","auditor"=>"Audit access only", default=> $r->description }; @endphp
<option value="{{ $r->id }}" {{ collect(old("roles"))->contains($r->id) ? "selected" : "" }}>{{ $label }} — {{ $desc }}</option>
@endforeach
</select>
@error("roles")<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
<div class="form-text" style="font-size:12px">Roles determine permissions and audit visibility. Choose one role per user.</div>
</div>
<div class="d-flex gap-2">
<button class="btn btn-primary focus-ring"><i class="bi bi-person-plus me-1"></i> Create</button>
<a href="{{ route("users.index") }}" class="btn btn-outline-secondary">Cancel</a>
</div>
</form>
@endsection
