@extends("layouts.bootstrap")
@section("title","Models")
@section("content")
<div class="d-flex justify-content-between align-items-center mb-4">
    <div><h2 class="mb-1" style="font-weight:700;">Model Versions</h2><p class="text-muted mb-0" style="font-size:13px;">Registry — text plus color for license</p></div>
    <a href="{{ route("model-versions.create") }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i> Add Model</a>
</div>

@if($models->isEmpty())
    <div class="card p-5 text-center">
        <div class="mx-auto mb-3 d-flex align-items-center justify-content-center" style="width:48px;height:48px;background:#f1f5f9;border-radius:12px;"><i class="bi bi-box-seam text-muted" style="font-size:20px;"></i></div>
        <h5>No models</h5><p class="text-muted" style="font-size:13px;">Add yolo11n entry with checksum and license.</p>
    </div>
@else
    <div class="card">
        <div class="card-header bg-white d-flex justify-content-between align-items-center" style="border-bottom:1px solid #e2e8f0;">
            <h5 class="mb-0" style="font-size:13px;letter-spacing:0.06em;text-transform:uppercase;"><i class="bi bi-cpu me-2 text-primary"></i>Registry</h5>
            <span class="badge bg-dark status-badge">{{ $models->total() }} versions</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0" style="font-size:13px;">
                <thead><tr><th>Name</th><th>Version</th><th>Checksum</th><th>License</th><th>Actions</th></tr></thead>
                <tbody>
                    @foreach($models as $m)
                    <tr>
                        <td style="font-weight:500;"><i class="bi bi-file-earmark-code me-2 text-muted"></i>{{ $m->name }}</td>
                        <td><span class="badge bg-primary status-badge">{{ $m->version }}</span></td>
                        <td style="font-family:monospace;font-size:12px;">{{ Str::limit($m->checksum_sha256,16) }} <i class="bi bi-shield-check text-success ms-1"></i></td>
                        <td><span class="badge @if($m->license=="AGPL-3.0") bg-dark @else bg-secondary @endif status-badge">{{ $m->license }}</span></td>
                        <td><a href="{{ route("model-versions.show",$m) }}" class="btn btn-sm btn-outline-primary">View</a></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-white d-flex justify-content-between align-items-center" style="font-size:12px;color:#64748b;"><span>Showing {{ $models->firstItem() }}–{{ $models->lastItem() }}</span> {{ $models->links() }}</div>
    </div>
@endif
@endsection
