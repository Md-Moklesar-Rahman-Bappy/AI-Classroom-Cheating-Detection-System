@extends("layouts.bootstrap")
@section("title","Users")
@section("content")
<div class="d-flex justify-content-between mb-3"><h2>Users & Roles</h2><a href="{{ route("users.create") }}" class="btn btn-primary">Add User</a></div>
<div class="table-responsive"><table class="table"><thead><tr><th>Name</th><th>Email</th><th>Roles</th><th>Actions</th></tr></thead><tbody>@foreach($users as $u)<tr><td>{{ $u->name }}</td><td>{{ $u->email }}</td><td>@foreach($u->roles as $r)<span class="badge bg-info">{{ $r->name }}</span> @endforeach</td><td><a href="{{ route("users.edit",$u) }}" class="btn btn-sm btn-outline-secondary">Edit</a></td></tr>@endforeach</tbody></table>{{ $users->links() }}</div>
@endsection
