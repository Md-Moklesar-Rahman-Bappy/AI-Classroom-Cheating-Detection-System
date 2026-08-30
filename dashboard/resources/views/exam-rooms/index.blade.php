@extends("layouts.bootstrap")
@section("title","Rooms")
@section("content")
<div class="d-flex justify-content-between align-items-center mb-3"><h2>Exam Rooms</h2><a href="{{ route("exam-rooms.create") }}" class="btn btn-primary">Add Room</a></div>
@if($rooms->isEmpty())<div class="card p-4 text-center text-muted">No rooms yet. Create your first room.</div>
@else<div class="table-responsive"><table class="table table-hover"><thead><tr><th>Name</th><th>Building</th><th>Capacity</th><th>Actions</th></tr></thead><tbody>@foreach($rooms as $r)<tr><td>{{ $r->name }}</td><td>{{ $r->building }}</td><td>{{ $r->capacity }}</td><td><a href="{{ route("exam-rooms.show",$r) }}" class="btn btn-sm btn-outline-primary">View</a> <a href="{{ route("exam-rooms.edit",$r) }}" class="btn btn-sm btn-outline-secondary">Edit</a></td></tr>@endforeach</tbody></table>{{ $rooms->links() }}</div>@endif
@endsection
