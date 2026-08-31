@extends("layouts.bootstrap")
@section("title","Analysis Jobs")
@section("content")
<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
    <div><h1 class="h4 mb-1" style="font-weight:700;letter-spacing:-.02em">Analysis Jobs</h1><p class="text-muted mb-0" style="font-size:13px">Recorded & live jobs — status, progress, remote sync, retry/cancel</p></div>
    <a href="{{ route("analysis-jobs.create") }}" class="btn btn-primary focus-ring"><i class="bi bi-plus-lg me-1" aria-hidden="true"></i> New Job</a>
</div>

@if($jobs->isEmpty())
    <div class="card empty-state">
        <div class="empty-icon"><i class="bi bi-cpu" aria-hidden="true"></i></div>
        <h2 class="h5">No jobs yet</h2>
        <p class="text-muted mx-auto" style="max-width:420px;font-size:13px">Create a job from a valid video asset, session and active model version. Use Database queue worker to process.</p>
        <a href="{{ route("analysis-jobs.create") }}" class="btn btn-primary mt-2">Create first job</a>
    </div>
@else
    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" style="font-size:13px">
                <caption class="visually-hidden">Analysis jobs — ID, session, status, progress, actions</caption>
                <thead><tr><th>ID</th><th>Session</th><th>Source</th><th>Model</th><th>Status</th><th>Progress</th><th>Created</th><th style="width:320px">Actions</th></tr></thead>
                <tbody>
                @foreach($jobs as $j)
                <tr>
                    <td><code class="text-mono" title="{{ $j->id }}">{{ Str::limit($j->id,8) }}</code></td>
                    <td><div class="fw-medium">{{ $j->session->name ?? "—" }}</div><div class="text-muted" style="font-size:11px">ID {{ Str::limit($j->exam_session_id,8) }}</div></td>
                    <td><span class="badge bg-light text-dark border status-badge">{{ $j->source_type }}</span></td>
                    <td class="text-muted" style="font-size:12px">{{ $j->modelVersion->name ?? "—" }}</td>
                    <td><span class="badge @if($j->status=="completed") bg-success @elseif($j->status=="failed") bg-danger @elseif(in_array($j->status,["processing","queued"])) bg-primary @elseif($j->status=="cancelled") bg-secondary @else bg-warning text-dark @endif status-badge"><i class="bi @if($j->status=="completed") bi-check-circle @elseif($j->status=="failed") bi-x-circle @elseif(in_array($j->status,["processing","queued"])) bi-hourglass-split @elseif($j->status=="cancelled") bi-slash-circle @else bi-clock @endif me-1" aria-hidden="true"></i>{{ $j->status }}</span></td>
                    <td style="min-width:120px"><div class="d-flex align-items-center gap-2"><div class="progress flex-grow-1" style="height:6px"><div class="progress-bar @if($j->status=="failed") bg-danger @elseif($j->status=="completed") bg-success @else bg-primary @endif" style="width:{{ $j->progress_percent }}%"></div></div><span style="font-variant-numeric:tabular-nums;font-size:12px">{{ $j->progress_percent }}%</span></div></td>
                    <td class="text-muted" style="font-size:12px">{{ $j->created_at?->format('Y-m-d H:i') ?? "—" }}</td>
                    <td>
                        <div class="btn-group btn-group-sm flex-wrap" role="group" aria-label="Job actions">
                            <a href="{{ route("analysis-jobs.show",$j) }}" class="btn btn-outline-primary focus-ring"><i class="bi bi-eye me-1" aria-hidden="true"></i> View</a>
                            @can("update",$j)<a href="{{ route("analysis-jobs.edit",$j) }}" class="btn btn-outline-secondary focus-ring">Edit</a>@endcan
                            @if(in_array($j->status,["queued","processing","pending"])) @can("cancel",$j)<form method="POST" action="{{ route("analysis-jobs.cancel",$j) }}" class="d-inline cancel-form">@csrf<button class="btn btn-outline-warning focus-ring">Cancel</button></form>@endcan @endif
                            @if(in_array($j->status,["failed","cancelled"])) @can("retry",$j)<form method="POST" action="{{ route("analysis-jobs.retry",$j) }}" class="d-inline retry-form">@csrf<button class="btn btn-outline-info focus-ring">Retry</button></form>@endcan @endif
                            @if($j->status=="completed") @can("report",$j)<a href="{{ route("reports.show",$j) }}" class="btn btn-outline-success focus-ring">Report</a>@endcan @endif
                            @can("delete",$j)<form method="POST" action="{{ route("analysis-jobs.destroy",$j) }}" class="d-inline delete-form">@csrf @method("DELETE")<button class="btn btn-outline-danger focus-ring">Delete</button></form>@endcan
                        </div>
                    </td>
                </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-white d-flex flex-column flex-sm-row justify-content-between align-items-center gap-2" style="font-size:12px;color:var(--color-text-muted)"><span>Showing {{ $jobs->firstItem() }}–{{ $jobs->lastItem() }} of {{ $jobs->total() }}</span>{{ $jobs->links() }}</div>
    </div>
@endif
@push("scripts")
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.querySelectorAll(".delete-form").forEach(f=>{f.addEventListener("submit",e=>{e.preventDefault();Swal.fire({title:"Delete job?",text:"Soft delete — recoverable.",icon:"warning",showCancelButton:true,confirmButtonColor:"#DC2626",confirmButtonText:"Delete"}).then(r=>{if(r.isConfirmed) f.submit()})})});
document.querySelectorAll(".cancel-form").forEach(f=>{f.addEventListener("submit",e=>{e.preventDefault();Swal.fire({title:"Cancel job?",icon:"question",showCancelButton:true,confirmButtonText:"Cancel"}).then(r=>{if(r.isConfirmed) f.submit()})})});
document.querySelectorAll(".retry-form").forEach(f=>{f.addEventListener("submit",e=>{e.preventDefault();Swal.fire({title:"Retry job?",text:"Creates new attempt with same config.",icon:"question",showCancelButton:true,confirmButtonText:"Retry"}).then(r=>{if(r.isConfirmed) f.submit()})})});
</script>
@endpush
@endsection
