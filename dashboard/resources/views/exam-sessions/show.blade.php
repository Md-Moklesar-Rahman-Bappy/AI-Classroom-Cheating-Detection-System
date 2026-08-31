@extends("layouts.bootstrap")
@section("title","Session Detail")
@section("content")
<div class="d-flex flex-column flex-md-row justify-content-between align-items-start gap-3 mb-4">
<div><h1 class="h4 mb-1" style="font-weight:700;letter-spacing:-.02em">{{ $examSession->name }}</h1><p class="text-muted mb-0" style="font-size:13px"><code class="text-mono" style="font-size:11px">{{ $examSession->id }}</code> • {{ $examSession->created_at?->format('Y-m-d H:i') ?? "—" }}</p></div>
<div class="d-flex gap-2 flex-wrap"><a href="{{ route("exam-sessions.index") }}" class="btn btn-outline-secondary btn-sm focus-ring">Back</a><a href="{{ route("exam-sessions.edit",$examSession) }}" class="btn btn-primary btn-sm focus-ring">Edit</a><form method="POST" action="{{ route("exam-sessions.destroy",$examSession) }}" class="d-inline delete-form">@csrf @method("DELETE")<button class="btn btn-outline-danger btn-sm focus-ring">Delete</button></form></div>
</div>
<div class="row g-4">
<div class="col-12 col-lg-8">
<div class="card">
<div class="card-header bg-white"><h2 class="h6 mb-0" style="font-size:11px;letter-spacing:.06em;text-transform:uppercase"><i class="bi bi-calendar3 me-2 text-primary" aria-hidden="true"></i>Session Details</h2></div>
<div class="card-body">
<div class="row g-3" style="font-size:13px">
<div class="col-6"><div class="text-muted" style="font-size:11px;letter-spacing:.06em;text-transform:uppercase">Room</div><div><span class="badge bg-light text-dark border status-badge"><i class="bi bi-building me-1" aria-hidden="true"></i> {{ $examSession->room->name ?? "No room assigned" }}</span></div></div>
<div class="col-6"><div class="text-muted" style="font-size:11px;letter-spacing:.06em;text-transform:uppercase">Status</div><div><span class="badge @if($examSession->status=="active") bg-success @elseif($examSession->status=="pending") bg-warning text-dark @elseif($examSession->status=="completed") bg-primary @else bg-secondary @endif status-badge"><i class="bi @if($examSession->status=="active") bi-play-circle @else bi-circle @endif me-1" aria-hidden="true"></i>{{ $examSession->status }}</span></div></div>
<div class="col-6"><div class="text-muted" style="font-size:11px;letter-spacing:.06em;text-transform:uppercase">Created</div><div>{{ $examSession->created_at?->format('Y-m-d H:i') ?? "—" }}</div></div>
<div class="col-6"><div class="text-muted" style="font-size:11px;letter-spacing:.06em;text-transform:uppercase">Updated</div><div>{{ $examSession->updated_at?->format('Y-m-d H:i') ?? "—" }}</div></div>
</div>
</div>
</div>
</div>
<div class="col-12 col-lg-4">
<div class="card">
<div class="card-header bg-white"><h2 class="h6 mb-0" style="font-size:11px;letter-spacing:.06em;text-transform:uppercase">Actions</h2></div>
<div class="card-body d-grid gap-2" style="font-size:13px">
<a href="{{ route("live.index") }}" class="btn btn-outline-danger btn-sm focus-ring"><i class="bi bi-broadcast me-1" aria-hidden="true"></i> Live Monitoring</a>
<a href="{{ route("video-assets.index") }}" class="btn btn-outline-primary btn-sm focus-ring"><i class="bi bi-collection-play me-1" aria-hidden="true"></i> Video Assets</a>
<a href="{{ route("analysis-jobs.index") }}" class="btn btn-outline-success btn-sm focus-ring"><i class="bi bi-cpu me-1" aria-hidden="true"></i> Analysis Jobs</a>
</div>
</div>
</div>
</div>
@push("scripts")
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>document.querySelectorAll(".delete-form").forEach(f=>f.addEventListener("submit",e=>{e.preventDefault();Swal.fire({title:"Delete session?",icon:"warning",showCancelButton:true,confirmButtonColor:"#DC2626",confirmButtonText:"Delete"}).then(r=>{if(r.isConfirmed)f.submit()})}))</script>
@endpush
@endsection
