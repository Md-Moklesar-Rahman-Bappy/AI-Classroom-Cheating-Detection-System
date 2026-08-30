@extends("layouts.bootstrap")
@section("title","Model Versions")
@section("content")
<div class="d-flex justify-content-between mb-3"><h2>Model Versions</h2><a href="{{ route("model-versions.create") }}" class="btn btn-primary">Add Model</a></div>
@if($models->isEmpty())<div class="card p-4 text-center text-muted">No models. Add yolo11n entry.</div>
@else<div class="table-responsive"><table class="table"><thead><tr><th>Name</th><th>Version</th><th>Checksum</th><th>License</th></tr></thead><tbody>@foreach($models as $m)<tr><td>{{ $m->name }}</td><td>{{ $m->version }}</td><td class="small">{{ Str::limit($m->checksum_sha256,12) }}</td><td><span class="badge bg-dark">{{ $m->license }}</span></td></tr>@endforeach</tbody></table>{{ $models->links() }}</div>@endif
@endsection
