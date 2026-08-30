@extends("layouts.bootstrap")
@section("title","Session Detail")
@section("content")
<h2>{{ $examSession->name }}</h2>
<div class="card p-3"><p>Status: <span class="badge bg-info">{{ $examSession->status }}</span></p><p>Room: {{ $examSession->room->name ?? "—" }}</p></div>
<div class="mt-3"><a href="{{ route("exam-sessions.edit",$examSession) }}" class="btn btn-secondary">Edit</a></div>
@endsection
