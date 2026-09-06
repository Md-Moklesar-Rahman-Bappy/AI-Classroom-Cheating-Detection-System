@extends("layouts.bootstrap")
@section("title","Edit User")
@section("content")
<div class="d-flex justify-content-between align-items-center mb-4">
<div><h1 class="h5 mb-1" style="font-weight:700">Edit {{ $user->name }}</h1><p class="text-muted mb-0" style="font-size:13px">Current role: @forelse($user->roles as $r){{ ucwords(str_replace("_"," ",$r->name)) }}@if(!$loop->last), @endif @empty No role assigned @endforelse</p></div>
<a href="{{ route("users.index") }}" class="btn btn-outline-secondary btn-sm">Back</a>
</div>
<form method="POST" action="{{ route("users.update",$user) }}" class="card p-4" style="max-width:640px">@csrf @method("PUT")
<div class="mb-3"><label class="form-label" for="name">Name</label><input type="text" id="name" name="name" value="{{ old("name",$user->name) }}" class="form-control @error("name") is-invalid @enderror" required><@error("name")<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
<div class="mb-3"><label class="form-label" for="email">Email</label><input type="email" id="email" name="email" value="{{ old("email",$user->email) }}" class="form-control @error("email") is-invalid @enderror" required><@error("email")<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
<div class="mb-3">
<label class="form-label" for="roles">Role <span class="text-danger">*</span></label>
<select name="roles[]" id="roles" class="form-select @error("roles") is-invalid @enderror" required>
@foreach($roles as $r)
@php $label = match($r->name){ "system_admin"=>"System Administrator","exam_admin"=>"Exam Administrator","invigilator"=>"Invigilator","reviewer"=>"Reviewer","auditor"=>"Auditor", default=> ucwords(str_replace("_"," ",$r->name)) }; $desc = match($r->name){ "system_admin"=>"Full system control","exam_admin"=>"Exam/session management","invigilator"=>"Monitoring only","reviewer"=>"Evidence review","auditor"=>"Audit access only", default=> $r->description }; $selected = $user->roles->contains($r) || collect(old("roles"))->contains($r->id); @endphp
<option value="{{ $r->id }}" {{ $selected ? "selected" : "" }}>{{ $label }} — {{ $desc }}</option>
@endforeach
</select>
@error("roles")<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
<div class="form-text" style="font-size:12px">Changing role will be audited. You cannot remove your own System Administrator role if you are the last admin.</div>
</div>
<div class="d-flex gap-2">
<button class="btn btn-primary" onclick="return confirm('Confirm role change? This will be audited.')">Update</button>
<a href="{{ route("users.index") }}" class="btn btn-outline-secondary">Cancel</a>
</div>
</form>
@endsection
