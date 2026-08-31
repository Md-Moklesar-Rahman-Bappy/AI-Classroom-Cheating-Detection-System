@extends("layouts.bootstrap")
@section("title","Sessions")
@section("content")
<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
<div><h1 class="h4 mb-1" style="font-weight:700;letter-spacing:-.02em">Exam Sessions</h1><p class="text-muted mb-0" style="font-size:13px">{{ $sessions->total() }} sessions — linked to rooms and detection jobs</p></div>
<a href="{{ route("exam-sessions.create") }}" class="btn btn-primary focus-ring"><i class="bi bi-plus-lg me-1" aria-hidden="true"></i> Add Session</a>
</div>
@if($sessions->isEmpty())
<div class="card empty-state">
<div class="empty-icon"><i class="bi bi-calendar3" aria-hidden="true"></i></div>
<h2 class="h5">No sessions</h2>
<p class="text-muted mx-auto" style="max-width:420px;font-size:13px">Create a session linked to a room. Sessions group video assets and analysis jobs.</p>
<a href="{{ route("exam-sessions.create") }}" class="btn btn-primary mt-2">Create session</a>
</div>
@else
<div class="card d-none d-md-block">
<div class="table-responsive">
<table class="table table-hover align-middle mb-0" style="font-size:13px">
<caption class="visually-hidden">Exam sessions — name, room, status</caption>
<thead><tr><th style="width:40px">SL</th><th>Name</th><th>Room</th><th>Status</th><th>Created</th><th style="width:120px">Actions</th></tr></thead>
<tbody>
@foreach($sessions as $i => $s)
<tr>
<td class="text-muted" style="font-variant-numeric:tabular-nums">{{ $sessions->firstItem()+$i }}</td>
<td><div class="fw-medium">{{ $s->name }}</div><div class="text-muted text-mono" style="font-size:11px">ID {{ Str::limit($s->id,8) }}</div></td>
<td><span class="badge bg-light text-dark border status-badge"><i class="bi bi-building me-1" aria-hidden="true"></i> {{ $s->room->name ?? "No room assigned" }}</span></td>
<td><span class="badge @if($s->status=="active") bg-success @elseif($s->status=="pending") bg-warning text-dark @elseif($s->status=="completed") bg-primary @elseif($s->status=="cancelled") bg-secondary @else bg-light text-dark border @endif status-badge"><i class="bi @if($s->status=="active") bi-play-circle @elseif($s->status=="pending") bi-hourglass @elseif($s->status=="completed") bi-check-circle @elseif($s->status=="cancelled") bi-slash-circle @else bi-circle @endif me-1" aria-hidden="true"></i>{{ $s->status }}</span></td>
<td class="text-muted" style="font-size:12px">{{ $s->created_at?->format('Y-m-d') ?? "—" }}</td>
<td><a href="{{ route("exam-sessions.show",$s) }}" class="btn btn-sm btn-outline-primary focus-ring"><i class="bi bi-eye me-1" aria-hidden="true"></i> View</a></td>
</tr>
@endforeach
</tbody>
</table>
</div>
<div class="card-footer bg-white d-flex flex-column flex-sm-row justify-content-between align-items-center gap-2" style="font-size:12px;color:var(--color-text-muted)"><span>Showing {{ $sessions->firstItem() }}–{{ $sessions->lastItem() }} of {{ $sessions->total() }}</span>{{ $sessions->links() }}</div>
</div>
<div class="d-md-none">
@foreach($sessions as $s)
<div class="card p-3 mb-2">
<div class="d-flex justify-content-between align-items-start gap-2"><div class="fw-medium">{{ $s->name }}</div><span class="badge @if($s->status=="active") bg-success @else bg-warning text-dark @endif status-badge">{{ $s->status }}</span></div>
<div class="text-muted" style="font-size:12px">{{ $s->room->name ?? "No room assigned" }}</div>
<div class="mt-3"><a href="{{ route("exam-sessions.show",$s) }}" class="btn btn-sm btn-outline-primary">View</a></div>
</div>
@endforeach
<div class="d-flex justify-content-center mt-3">{{ $sessions->links() }}</div>
</div>
@endif
@endsection
