@extends("layouts.bootstrap")
@section("title","Edit User")
@section("content")
<h2>Edit {{ $user->name }}</h2>
<form method="POST" action="{{ route("users.update",$user) }}">@csrf @method("PUT")
<div class="mb-3"><label class="form-label">Name</label><input type="text" name="name" value="{{ $user->name }}" class="form-control" required></div>
<div class="mb-3"><label class="form-label">Email</label><input type="email" name="email" value="{{ $user->email }}" class="form-control" required></div>
<div class="mb-3"><label class="form-label">Roles</label><select name="roles[]" class="form-select" multiple required>@foreach($roles as $r)<option value="{{ $r->id }}" @selected($user->roles->contains($r))>{{ $r->name }}</option>@endforeach</select></div>
<button class="btn btn-primary">Update</button>
</form>
@endsection
