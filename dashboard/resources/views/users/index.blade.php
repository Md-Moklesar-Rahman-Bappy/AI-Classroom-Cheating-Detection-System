@extends("layouts.bootstrap")
@section("title","Users")
@section("content")
<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
<div><h1 class="h4 mb-1" style="font-weight:700;letter-spacing:-.02em">Users & Roles</h1><p class="text-muted mb-0" style="font-size:13px">{{ $users->total() }} users — role badges with text+color+icon</p></div>
<a href="{{ route("users.create") }}" class="btn btn-primary focus-ring"><i class="bi bi-person-plus me-1" aria-hidden="true"></i> Add User</a>
</div>

<form method="GET" action="{{ route("users.index") }}" class="card p-3 mb-3 d-flex flex-column flex-md-row gap-2 align-items-md-end">
<div style="flex:1"><label class="form-label" style="font-size:11px;letter-spacing:.06em;text-transform:uppercase;color:#64748B;font-weight:600">Search</label><input type="text" name="search" value="{{ request("search") }}" placeholder="Name or email" class="form-control form-control-sm"></div>
<div style="min-width:180px"><label class="form-label" style="font-size:11px;letter-spacing:.06em;text-transform:uppercase;color:#64748B;font-weight:600">Filter by role</label><select name="role" class="form-select form-select-sm"><option value="">All roles</option>@foreach($roles as $r)<option value="{{ $r->name }}" {{ request("role")==$r->name ? "selected" : "" }}>{{ ucwords(str_replace("_"," ",$r->name)) }}</option>@endforeach</select></div>
<div style="min-width:140px"><label class="form-label" style="font-size:11px;letter-spacing:.06em;text-transform:uppercase;color:#64748B;font-weight:600">Sort</label><select name="sort" class="form-select form-select-sm"><option value="name" {{ request("sort")=="name" ? "selected" : "" }}>Name</option><option value="email" {{ request("sort")=="email" ? "selected" : "" }}>Email</option><option value="created_at" {{ request("sort")=="created_at" ? "selected" : "" }}>Created</option></select></div>
<div><button class="btn btn-sm btn-outline-primary">Apply</button> <a href="{{ route("users.index") }}" class="btn btn-sm btn-outline-secondary">Clear</a></div>
</form>

@if($users->isEmpty())
<div class="card empty-state">
<div class="empty-icon"><i class="bi bi-people" aria-hidden="true"></i></div>
<h2 class="h5">No users</h2>
<p class="text-muted" style="font-size:13px">Create the first user account.</p>
</div>
@else
<div class="card d-none d-md-block">
<div class="table-responsive">
<table class="table table-hover align-middle mb-0" style="font-size:13px">
<caption class="visually-hidden">Users — name, email, roles, actions</caption>
<thead><tr><th><a href="{{ route("users.index", array_merge(request()->all(), ["sort"=>"name","dir"=> request("sort")=="name" && request("dir")!="desc" ? "desc":"asc"])) }}" class="text-decoration-none text-muted">Name @if(request("sort")=="name")<i class="bi bi-arrow-{{ request("dir")=="desc" ? "down":"up" }}"></i>@endif</a></th><th><a href="{{ route("users.index", array_merge(request()->all(), ["sort"=>"email","dir"=> request("sort")=="email" && request("dir")!="desc" ? "desc":"asc"])) }}" class="text-decoration-none text-muted">Email</a></th><th>Roles</th><th style="width:120px">Actions</th></tr></thead>
<tbody>
@foreach($users as $u)
<tr>
<td><div class="d-flex align-items-center gap-2"><span class="d-flex align-items-center justify-content-center flex-shrink-0" style="width:28px;height:28px;border-radius:50%;background:var(--color-primary-soft);color:var(--color-primary);font-size:11px;font-weight:700">{{ strtoupper(substr($u->name,0,1)) }}</span><span class="fw-medium">{{ $u->name }}</span></div></td>
<td><span class="text-mono" style="font-size:12px">{{ $u->email }}</span></td>
<td>
@foreach($u->roles as $r)
@php $color = match($r->name){ "system_admin"=>"bg-danger", "exam_admin"=>"bg-warning text-dark", "invigilator"=>"bg-primary", "reviewer"=>"bg-success", "auditor"=>"bg-secondary", default=>"bg-secondary"}; $icon = match($r->name){ "system_admin"=>"bi-shield-lock", "exam_admin"=>"bi-briefcase", "invigilator"=>"bi-camera-video", "reviewer"=>"bi-eye", "auditor"=>"bi-journal-text", default=>"bi-person"}; $isPurple = $r->name=="auditor"; @endphp
<span class="badge {{ $color }} status-badge me-1" @if($isPurple) style="background:#7C3AED !important;color:#fff" @endif><i class="bi {{ $icon }} me-1" aria-hidden="true"></i>{{ ucwords(str_replace("_"," ",$r->name)) }}</span>
@endforeach
@if($u->roles->isEmpty())<span class="text-muted" style="font-size:12px">No role assigned</span>@endif
</td>
<td><a href="{{ route("users.edit",$u) }}" class="btn btn-sm btn-outline-secondary focus-ring"><i class="bi bi-pencil me-1" aria-hidden="true"></i> Edit</a></td>
</tr>
@endforeach
</tbody>
</table>
</div>
<div class="card-footer bg-white d-flex flex-column flex-sm-row justify-content-between align-items-center gap-2" style="font-size:12px;color:var(--color-text-muted)"><span>Showing {{ $users->firstItem() }}–{{ $users->lastItem() }} of {{ $users->total() }}</span>{{ $users->links() }}</div>
</div>
<div class="d-md-none">
@foreach($users as $u)
<div class="card p-3 mb-2">
<div class="d-flex justify-content-between align-items-start gap-2">
<div><div class="fw-medium">{{ $u->name }}</div><div class="text-muted text-mono" style="font-size:11px">{{ $u->email }}</div></div>
<a href="{{ route("users.edit",$u) }}" class="btn btn-sm btn-outline-secondary focus-ring">Edit</a>
</div>
<div class="mt-2 d-flex flex-wrap gap-1">
@foreach($u->roles as $r)@php $color = match($r->name){ "system_admin"=>"bg-danger", "exam_admin"=>"bg-warning text-dark", "invigilator"=>"bg-primary", "reviewer"=>"bg-success", "auditor"=>"bg-secondary", default=>"bg-secondary"}; $isPurple=$r->name=="auditor"; @endphp<span class="badge {{ $color }} status-badge" @if($isPurple) style="background:#7C3AED !important;color:#fff" @endif>{{ ucwords(str_replace("_"," ",$r->name)) }}</span>@endforeach
</div>
</div>
@endforeach
<div class="d-flex justify-content-center mt-3">{{ $users->links() }}</div>
</div>
@endif
@endsection
