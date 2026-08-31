@extends("layouts.bootstrap")
@section("title","Models")
@section("content")
<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
<div><h1 class="h4 mb-1" style="font-weight:700;letter-spacing:-.02em">Model Versions</h1><p class="text-muted mb-0" style="font-size:13px">Registry — checksum with copy, license with text+color</p></div>
<a href="{{ route("model-versions.create") }}" class="btn btn-primary focus-ring"><i class="bi bi-plus-lg me-1" aria-hidden="true"></i> Add Model</a>
</div>

@if($models->isEmpty())
<div class="card empty-state">
<div class="empty-icon"><i class="bi bi-box-seam" aria-hidden="true"></i></div>
<h2 class="h5">No models</h2>
<p class="text-muted mx-auto" style="max-width:420px;font-size:13px">Add a model entry with SHA-256 checksum and license for auditability.</p>
<a href="{{ route("model-versions.create") }}" class="btn btn-primary mt-2">Add model</a>
</div>
@else
<div class="card d-none d-md-block">
<div class="card-header bg-white d-flex justify-content-between align-items-center"><h2 class="h6 mb-0" style="font-size:11px;letter-spacing:.06em;text-transform:uppercase"><i class="bi bi-cpu me-2 text-primary" aria-hidden="true"></i>Registry</h2><span class="badge bg-dark status-badge">{{ $models->total() }} versions</span></div>
<div class="table-responsive">
<table class="table table-hover align-middle mb-0" style="font-size:13px">
<caption class="visually-hidden">Model versions — name, version, checksum, license</caption>
<thead><tr><th>Name</th><th>Version</th><th>Checksum (SHA-256)</th><th>License</th><th style="width:120px">Actions</th></tr></thead>
<tbody>
@foreach($models as $m)
<tr>
<td><div class="fw-medium d-flex align-items-center gap-2"><i class="bi bi-file-earmark-code text-muted" aria-hidden="true"></i> {{ $m->name }}</div><div class="text-muted text-mono" style="font-size:11px">ID {{ Str::limit($m->id,8) }}</div></td>
<td><span class="badge bg-primary status-badge">{{ $m->version }}</span></td>
<td><code class="text-mono" style="font-size:11px;word-break:break-all" title="{{ $m->checksum_sha256 }}">{{ Str::limit($m->checksum_sha256,20) }}</code> @if($m->checksum_sha256)<button class="btn btn-sm btn-link p-0 ms-1" onclick="navigator.clipboard.writeText('{{ $m->checksum_sha256 }}')" aria-label="Copy checksum" title="Copy checksum"><i class="bi bi-copy" style="font-size:12px" aria-hidden="true"></i></button> <i class="bi bi-shield-check text-success ms-1" aria-hidden="true" title="Verified"></i>@endif</td>
<td><span class="badge @if($m->license=="AGPL-3.0") bg-dark @else bg-secondary @endif status-badge"><i class="bi bi-file-text me-1" aria-hidden="true"></i>{{ $m->license }}</span></td>
<td><a href="{{ route("model-versions.show",$m) }}" class="btn btn-sm btn-outline-primary focus-ring"><i class="bi bi-eye me-1" aria-hidden="true"></i> View</a></td>
</tr>
@endforeach
</tbody>
</table>
</div>
<div class="card-footer bg-white d-flex flex-column flex-sm-row justify-content-between align-items-center gap-2" style="font-size:12px;color:var(--color-text-muted)"><span>Showing {{ $models->firstItem() }}–{{ $models->lastItem() }} of {{ $models->total() }}</span>{{ $models->links() }}</div>
</div>
<div class="d-md-none">
@foreach($models as $m)
<div class="card p-3 mb-2">
<div class="d-flex justify-content-between align-items-start gap-2"><div class="fw-medium">{{ $m->name }}</div><span class="badge bg-primary status-badge">{{ $m->version }}</span></div>
<div class="text-mono text-muted mt-1 d-flex align-items-center gap-1" style="font-size:11px;word-break:break-all">{{ Str::limit($m->checksum_sha256,24) }} <button class="btn btn-sm btn-link p-0" onclick="navigator.clipboard.writeText('{{ $m->checksum_sha256 }}')" aria-label="Copy checksum"><i class="bi bi-copy" aria-hidden="true"></i></button></div>
<div class="mt-1"><span class="badge @if($m->license=="AGPL-3.0") bg-dark @else bg-secondary @endif status-badge">{{ $m->license }}</span></div>
<div class="mt-3"><a href="{{ route("model-versions.show",$m) }}" class="btn btn-sm btn-outline-primary">View</a></div>
</div>
@endforeach
<div class="d-flex justify-content-center mt-3">{{ $models->links() }}</div>
</div>
@endif
@endsection
