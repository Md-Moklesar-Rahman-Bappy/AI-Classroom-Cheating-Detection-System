@extends("layouts.bootstrap")
@section("title","Analysis Jobs")
@section("content")
<div class="d-flex justify-content-between mb-3"><h2>Analysis Jobs</h2><a href="{{ route("analysis-jobs.create") }}" class="btn btn-primary">New Job</a></div>
@if($jobs->isEmpty())<div class="card p-4 text-center text-muted">No jobs.</div>
@else<div class="table-responsive"><table class="table"><thead><tr><th>ID</th><th>Session</th><th>Status</th><th>Progress</th><th>Actions</th></tr></thead><tbody>
@foreach($jobs as $j)
<tr>
<td>{{ Str::limit($j->id,8) }}</td>
<td>{{ $j->session->name ?? "—" }}</td>
<td><span class="badge @if($j->status=="completed") bg-success @elseif($j->status=="failed") bg-danger @elseif($j->status=="processing"||$j->status=="queued") bg-primary @elseif($j->status=="cancelled") bg-secondary @else bg-warning text-dark @endif">{{ $j->status }}</span></td>
<td>{{ $j->progress_percent }}%</td>
<td>
<div class="btn-group btn-group-sm">
<a href="{{ route("analysis-jobs.show",$j) }}" class="btn btn-outline-primary">View</a>
@can("update", $j)<a href="{{ route("analysis-jobs.edit",$j) }}" class="btn btn-outline-secondary">Edit</a>@endcan
@if(in_array($j->status,["queued","processing"])) @can("cancel", $j)<form method="POST" action="{{ route("analysis-jobs.cancel",$j) }}" class="d-inline cancel-form">@csrf<button class="btn btn-outline-warning">Cancel</button></form>@endcan @endif
@if(in_array($j->status,["failed","cancelled"])) @can("retry", $j)<form method="POST" action="{{ route("analysis-jobs.retry",$j) }}" class="d-inline retry-form">@csrf<button class="btn btn-outline-info">Retry</button></form>@endcan @endif
@if($j->status=="completed") @can("report", $j)<a href="{{ route("reports.show",$j) }}" class="btn btn-outline-success">Report</a>@endcan @endif
@can("delete", $j)<form method="POST" action="{{ route("analysis-jobs.destroy",$j) }}" class="d-inline delete-form">@csrf @method("DELETE")<button class="btn btn-outline-danger">Delete</button></form>@endcan
</div>
</td>
</tr>
@endforeach
</tbody></table>{{ $jobs->links() }}</div>
@endif
@push("scripts")
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.querySelectorAll(".delete-form").forEach(f=>{
    f.addEventListener("submit", e=>{
        e.preventDefault();
        Swal.fire({title:"Delete job?", text:"This will soft delete the job (recoverable).", icon:"warning", showCancelButton:true, confirmButtonColor:"#dc3545", confirmButtonText:"Delete"}).then(r=>{ if(r.isConfirmed) f.submit(); });
    });
});
document.querySelectorAll(".cancel-form").forEach(f=>{
    f.addEventListener("submit", e=>{
        e.preventDefault();
        Swal.fire({title:"Cancel job?", icon:"question", showCancelButton:true, confirmButtonText:"Cancel"}).then(r=>{ if(r.isConfirmed) f.submit(); });
    });
});
document.querySelectorAll(".retry-form").forEach(f=>{
    f.addEventListener("submit", e=>{
        e.preventDefault();
        Swal.fire({title:"Retry job?", icon:"question", showCancelButton:true, confirmButtonText:"Retry"}).then(r=>{ if(r.isConfirmed) f.submit(); });
    });
});
</script>
@endpush
@endsection
