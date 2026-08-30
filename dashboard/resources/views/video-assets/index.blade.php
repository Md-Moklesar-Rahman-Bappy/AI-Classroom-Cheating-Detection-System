@extends("layouts.bootstrap")
@section("title","Video Assets")
@section("content")
<div class="d-flex justify-content-between mb-3"><h2>Video Assets</h2><a href="{{ route('video-assets.create') }}" class="btn btn-primary">Upload Video</a></div>
@if($assets->isEmpty())<div class="card p-4 text-center text-muted">No videos. Upload a valid mp4/avi/mov/mkv.</div>
@else<div class="table-responsive"><table class="table"><thead><tr><th>SL</th><th>Original</th><th>Stored</th><th>Mime</th><th>Size</th><th>Created</th><th>Status</th><th>Linked Jobs</th><th>Actions</th></tr></thead><tbody>@foreach($assets as $i => $a)<tr><td>{{ $i + 1 }}</td><td>{{ $a->original_filename }}</td><td class="text-muted small">{{ Str::limit($a->stored_filename,20) }}</td><td>{{ $a->mime_type }}</td><td>{{ $a->size_bytes > 0 ? number_format($a->size_bytes) . ' B' : '-' }}</td><td>{{ $a->created_at ? $a->created_at->format('Y-m-d') : '-' }}</td><td><span class="badge @if($a->validation_status=="valid") bg-success @elseif($a->validation_status=="invalid") bg-danger @else bg-warning text-dark @endif">{{ $a->validation_status }}</span></td><td>{{ $a->linkedJobCount }}</td><td>
@if($a->validation_status=="valid")
<div class="btn-group btn-group-sm">
<a href="{{ route('video-assets.show',$a) }}" class="btn btn-outline-primary">View</a>
<a href="{{ route('analysis-jobs.create') }}" class="btn btn-outline-success">Analyze</a>
@can("edit", $a)<a href="{{ route('video-assets.edit',$a) }}" class="btn btn-outline-secondary">Edit</a>@endcan
@can("delete", $a)<form method="POST" action="{{ route('video-assets.destroy',$a) }}" class="d-inline delete-form">@csrf @method('DELETE')<button class="btn btn-outline-danger">Delete</button></form>@endcan
</div>
@else
<span class="text-muted small">Invalid asset</span>
@endif
</td></tr>@endforeach</tbody></table>{{ $assets->links() }}</div>@endif
@push("scripts")
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.querySelectorAll(".delete-form").forEach(f=>{
    f.addEventListener("submit", e=>{
        e.preventDefault();
        Swal.fire({title:"Delete video?", text:"This will soft delete the video asset (recoverable).", icon:"warning", showCancelButton:true, confirmButtonColor:"#dc3545", confirmButtonText:"Delete"}).then(r=>{ if(r.isConfirmed) f.submit(); });
    });
});
</script>
@endpush
@endsection
