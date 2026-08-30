@extends("layouts.bootstrap")
@section("title","Room Detail")
@section("content")
<h2>{{ $examRoom->name }}</h2>
<div class="card p-3"><p><strong>Building:</strong> {{ $examRoom->building ?? "—" }} <span class="badge bg-info status-badge">Info</span></p><p><strong>Capacity:</strong> {{ $examRoom->capacity ?? "—" }}</p><p><strong>Notes:</strong> {{ $examRoom->camera_position_notes ?? "—" }}</p></div>
<div class="mt-3"><a href="{{ route("exam-rooms.edit",$examRoom) }}" class="btn btn-secondary">Edit</a> <form method="POST" action="{{ route("exam-rooms.destroy",$examRoom) }}" class="d-inline">@csrf @method("DELETE")<button class="btn btn-danger" onclick="return confirm(\"Delete?\")">Delete</button></form></div>
@endsection
