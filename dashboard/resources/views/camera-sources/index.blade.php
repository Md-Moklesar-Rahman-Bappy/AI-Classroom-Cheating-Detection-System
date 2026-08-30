@extends("layouts.bootstrap")
@section("title","Cameras")
@section("content")
<div class="d-flex justify-content-between mb-3"><h2>Camera Sources</h2><a href="{{ route("camera-sources.create") }}" class="btn btn-primary">Add Camera</a></div>
<div class="alert alert-warning">Credentials are encrypted and never displayed.</div>
@if($sources->isEmpty())<div class="card p-4 text-center text-muted">No cameras. Add test source for development.</div>
@else<div class="table-responsive"><table class="table"><thead><tr><th>Name</th><th>Type</th><th>Identifier</th><th>Status</th><th>Has Credentials</th></tr></thead><tbody>@foreach($sources as $s)<tr><td>{{ $s->name }}</td><td>{{ $s->source_type }}</td><td>{{ Str::limit($s->identifier,40) }}</td><td><span class="badge bg-secondary">{{ $s->status }}</span></td><td>@if($s->has_credentials)<span class="badge bg-success">Yes</span>@else<span class="badge bg-secondary">No</span>@endif</td></tr>@endforeach</tbody></table>{{ $sources->links() }}</div>@endif
@endsection
