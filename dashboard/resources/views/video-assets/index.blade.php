@extends("layouts.bootstrap")
@section("title","Video Assets")
@section("content")
<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
    <div><h1 class="h4 mb-1" style="font-weight:700;letter-spacing:-.02em">Video Assets</h1><p class="text-muted mb-0" style="font-size:13px">Private storage — UUID filenames, validation, linked-job protection</p></div>
    <a href="{{ route('video-assets.create') }}" class="btn btn-primary focus-ring"><i class="bi bi-cloud-upload me-1" aria-hidden="true"></i> Upload Video</a>
</div>

@if($assets->isEmpty())
    <div class="card empty-state">
        <div class="empty-icon"><i class="bi bi-collection-play" aria-hidden="true"></i></div>
        <h2 class="h5">No videos yet</h2>
        <p class="text-muted mx-auto" style="max-width:420px;font-size:13px">Upload a valid mp4, avi, mov or mkv. Files are stored privately with UUID names and validated before analysis.</p>
        <a href="{{ route('video-assets.create') }}" class="btn btn-primary mt-2"><i class="bi bi-cloud-upload me-1" aria-hidden="true"></i> Upload first video</a>
    </div>
@else
    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" style="font-size:13px">
                <caption class="visually-hidden">Video assets list — original filename, stored ID, type, size, status, linked jobs, actions</caption>
                <thead><tr><th style="width:40px">SL</th><th>Original Filename</th><th>Stored Identifier</th><th>MIME</th><th>Size</th><th>Status</th><th>Linked Jobs</th><th>Created</th><th style="width:240px">Actions</th></tr></thead>
                <tbody>
                @foreach($assets as $i => $a)
                <tr>
                    <td class="text-muted" style="font-variant-numeric:tabular-nums">{{ $assets->firstItem()+$i }}</td>
                    <td><div class="fw-medium truncate" style="max-width:220px" title="{{ $a->original_filename }}">{{ $a->original_filename }}</div><div class="text-muted text-mono" style="font-size:11px">ID {{ Str::limit($a->id,8) }}</div></td>
                    <td><code class="text-mono" title="{{ $a->stored_filename }}">{{ Str::limit($a->stored_filename,18) }}</code> <button class="btn btn-sm btn-link p-0 ms-1" onclick="navigator.clipboard.writeText('{{ $a->stored_filename }}')" aria-label="Copy stored filename" title="Copy"><i class="bi bi-copy" aria-hidden="true" style="font-size:12px"></i></button></td>
                    <td><span class="badge bg-light text-dark border status-badge">{{ $a->mime_type }}</span></td>
                    <td style="font-variant-numeric:tabular-nums">{{ $a->size_bytes>0 ? number_format($a->size_bytes).' B' : '—' }}</td>
                    <td><span class="badge @if($a->validation_status=="valid") bg-success @elseif($a->validation_status=="invalid") bg-danger @else bg-warning text-dark @endif status-badge"><i class="bi @if($a->validation_status=="valid") bi-check-circle @elseif($a->validation_status=="invalid") bi-x-circle @else bi-hourglass @endif me-1" aria-hidden="true"></i>{{ $a->validation_status }}</span></td>
                    <td><span class="badge bg-dark status-badge"><i class="bi bi-link-45deg me-1" aria-hidden="true"></i>{{ $a->linkedJobCount ?? $a->analysisJobs()->count() }}</span></td>
                    <td class="text-muted" style="font-size:12px">{{ $a->created_at?->format('Y-m-d') ?? '—' }}</td>
                    <td>
                        @if($a->validation_status=="valid")
                        <div class="btn-group btn-group-sm" role="group" aria-label="Actions">
                            <a href="{{ route('video-assets.show',$a) }}" class="btn btn-outline-primary focus-ring" title="View"><i class="bi bi-eye" aria-hidden="true"></i> View</a>
                            <a href="{{ route('analysis-jobs.create') }}?video_asset_id={{ $a->id }}" class="btn btn-outline-success focus-ring" title="Analyze"><i class="bi bi-play" aria-hidden="true"></i> Analyze</a>
                            @can("edit",$a)<a href="{{ route('video-assets.edit',$a) }}" class="btn btn-outline-secondary focus-ring">Edit</a>@endcan
                            @can("delete",$a)<form method="POST" action="{{ route('video-assets.destroy',$a) }}" class="d-inline delete-form">@csrf @method('DELETE')<button class="btn btn-outline-danger focus-ring">Delete</button></form>@endcan
                        </div>
                        @else
                        <span class="text-muted small d-inline-flex align-items-center gap-1"><i class="bi bi-exclamation-triangle" aria-hidden="true"></i> Invalid asset — <a href="{{ route('video-assets.edit',$a) }}">edit</a></span>
                        @endif
                    </td>
                </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-white d-flex flex-column flex-sm-row justify-content-between align-items-center gap-2" style="font-size:12px;color:var(--color-text-muted)"><span>Showing {{ $assets->firstItem() }}–{{ $assets->lastItem() }} of {{ $assets->total() }}</span>{{ $assets->links() }}</div>
    </div>
    <div class="d-md-none mt-3">
        @foreach($assets as $a)
        <div class="card p-3 mb-2">
            <div class="d-flex justify-content-between align-items-start gap-2"><div class="fw-medium truncate" style="max-width:60%">{{ $a->original_filename }}</div><span class="badge @if($a->validation_status=="valid") bg-success @else bg-danger @endif status-badge">{{ $a->validation_status }}</span></div>
            <div class="text-muted text-mono mt-1" style="font-size:11px">{{ Str::limit($a->stored_filename,24) }} • {{ $a->mime_type }} • {{ $a->size_bytes>0?number_format($a->size_bytes).' B':'—' }}</div>
            <div class="d-flex gap-2 mt-3 flex-wrap"><a href="{{ route('video-assets.show',$a) }}" class="btn btn-sm btn-outline-primary">View</a><a href="{{ route('analysis-jobs.create') }}" class="btn btn-sm btn-success">Analyze</a></div>
        </div>
        @endforeach
    </div>
@endif
@push("scripts")
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.querySelectorAll(".delete-form").forEach(f=>{f.addEventListener("submit",e=>{e.preventDefault();Swal.fire({title:"Delete video?",text:"Soft delete — recoverable. Blocked if linked jobs exist.",icon:"warning",showCancelButton:true,confirmButtonColor:"#DC2626",confirmButtonText:"Delete"}).then(r=>{if(r.isConfirmed) f.submit()})})});
</script>
@endpush
@endsection
