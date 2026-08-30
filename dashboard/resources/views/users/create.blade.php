@extends("layouts.bootstrap")
@section("title","Create User")
@section("content")
<h2>Create User</h2>
<form method="POST" action="{{ route("users.store") }}">@csrf
<div class="mb-3"><label class="form-label">Name</label><input type="text" name="name" class="form-control" required></div>
<div class="mb-3"><label class="form-label">Email</label><input type="email" name="email" class="form-control" required></div>
<div class="mb-3"><label class="form-label">Password (min 8, letters+numbers+symbols)</label><input type="password" name="password" class="form-control" required></div>
<div class="mb-3"><label class="form-label">Roles</label><select name="roles[]" class="form-select" multiple required>@foreach($roles as $r)<option value="{{ $r->id }}">{{ $r->name }}</option>@endforeach</select></div>
<button class="btn btn-primary">Create</button>
</form>
@endsection
