@extends("layouts.bootstrap")
@section("title","Sessions")
@section("content")
<div class="d-flex justify-content-between mb-3"><h2>Exam Sessions</h2><a href="{{ route("exam-sessions.create") }}" class="btn btn-primary">Add Session</a></div>
@if($sessions->isEmpty())<div class="card p-4 text-center text-muted">No sessions.</div>
@else<div class="table-responsive"><table class="table"><thead><tr><th>Name</th><th>Room</th><th>Status <span class="badge bg-success">text+color</span></th><th>Actions</th></tr></thead><tbody>@foreach($sessions as $s)<tr><td>{{ $s->name }}</td><td>{{ $s->room->name ?? "—" }}</td><td><span class="badge @if($s->status=="active") bg-success @elseif($s->status=="pending") bg-warning text-dark @elseif($s->status=="completed") bg-primary @else bg-secondary @endif">{{ $s->status }}</span></td><td><a href="{{ route("exam-sessions.show",$s) }}" class="btn btn-sm btn-outline-primary">View</a></td></tr>@endforeach</tbody></table>{{ $sessions->links() }}</div>@endif
@endsection
