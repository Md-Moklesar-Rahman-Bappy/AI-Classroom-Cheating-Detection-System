@extends("layouts.bootstrap")
@section("title","Rooms")
@section("content")
<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
<div><h1 class="h4 mb-1" style="font-weight:700;letter-spacing:-.02em">Exam Rooms</h1><p class="text-muted mb-0" style="font-size:13px">{{ $rooms->total() }} rooms — building, capacity, camera notes</p></div>
<a href="{{ route("exam-rooms.create") }}" class="btn btn-primary focus-ring"><i class="bi bi-plus-lg me-1" aria-hidden="true"></i> Add Room</a>
</div>
@if($rooms->isEmpty())
<div class="card empty-state">
<div class="empty-icon"><i class="bi bi-building" aria-hidden="true"></i></div>
<h2 class="h5">No rooms yet</h2>
<p class="text-muted mx-auto" style="max-width:420px;font-size:13px">Create your first exam room with building and capacity.</p>
<a href="{{ route("exam-rooms.create") }}" class="btn btn-primary mt-2">Create room</a>
</div>
@else
<div class="card d-none d-md-block">
<div class="table-responsive">
<table class="table table-hover align-middle mb-0" style="font-size:13px">
<caption class="visually-hidden">Exam rooms list</caption>
<thead><tr><th style="width:40px">SL</th><th>Name</th><th>Building</th><th>Capacity</th><th>Created</th><th style="width:200px">Actions</th></tr></thead>
<tbody>
@foreach($rooms as $i => $r)
<tr>
<td class="text-muted" style="font-variant-numeric:tabular-nums">{{ $rooms->firstItem()+$i }}</td>
<td><div class="fw-medium">{{ $r->name }}</div><div class="text-muted text-mono" style="font-size:11px">ID {{ Str::limit($r->id,8) }}</div></td>
<td>{{ $r->building ?? "—" }}</td>
<td><span class="badge bg-light text-dark border status-badge"><i class="bi bi-people me-1" aria-hidden="true"></i> {{ $r->capacity ?? "—" }}</span></td>
<td class="text-muted" style="font-size:12px">{{ $r->created_at?->format('Y-m-d') ?? "—" }}</td>
<td><div class="btn-group btn-group-sm" role="group" aria-label="Room actions"><a href="{{ route("exam-rooms.show",$r) }}" class="btn btn-outline-primary focus-ring"><i class="bi bi-eye me-1" aria-hidden="true"></i> View</a><a href="{{ route("exam-rooms.edit",$r) }}" class="btn btn-outline-secondary focus-ring">Edit</a></div></td>
</tr>
@endforeach
</tbody>
</table>
</div>
<div class="card-footer bg-white d-flex flex-column flex-sm-row justify-content-between align-items-center gap-2" style="font-size:12px;color:var(--color-text-muted)"><span>Showing {{ $rooms->firstItem() }}–{{ $rooms->lastItem() }} of {{ $rooms->total() }}</span>{{ $rooms->links() }}</div>
</div>
<div class="d-md-none">
@foreach($rooms as $r)
<div class="card p-3 mb-2">
<div class="d-flex justify-content-between align-items-start gap-2"><div class="fw-medium">{{ $r->name }}</div><span class="badge bg-light text-dark border status-badge">{{ $r->capacity ?? "—" }} cap</span></div>
<div class="text-muted" style="font-size:12px">{{ $r->building ?? "No building" }}</div>
<div class="d-flex gap-2 mt-3"><a href="{{ route("exam-rooms.show",$r) }}" class="btn btn-sm btn-outline-primary">View</a><a href="{{ route("exam-rooms.edit",$r) }}" class="btn btn-sm btn-outline-secondary">Edit</a></div>
</div>
@endforeach
<div class="d-flex justify-content-center mt-3">{{ $rooms->links() }}</div>
</div>
@endif
@endsection
