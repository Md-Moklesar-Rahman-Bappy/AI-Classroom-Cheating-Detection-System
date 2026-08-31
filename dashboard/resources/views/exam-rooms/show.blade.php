@extends("layouts.bootstrap")
@section("title","Room Detail")
@section("content")
<div class="d-flex flex-column flex-md-row justify-content-between align-items-start gap-3 mb-4">
<div><h1 class="h4 mb-1" style="font-weight:700;letter-spacing:-.02em">{{ $examRoom->name }}</h1><p class="text-muted mb-0" style="font-size:13px"><code class="text-mono" style="font-size:11px">{{ $examRoom->id }}</code> • Created {{ $examRoom->created_at?->format('Y-m-d H:i') ?? "—" }}</p></div>
<div class="d-flex gap-2 flex-wrap"><a href="{{ route("exam-rooms.index") }}" class="btn btn-outline-secondary btn-sm focus-ring">Back</a><a href="{{ route("exam-rooms.edit",$examRoom) }}" class="btn btn-primary btn-sm focus-ring"><i class="bi bi-pencil me-1" aria-hidden="true"></i> Edit</a><form method="POST" action="{{ route("exam-rooms.destroy",$examRoom) }}" class="d-inline delete-form">@csrf @method("DELETE")<button class="btn btn-outline-danger btn-sm focus-ring">Delete</button></form></div>
</div>
<div class="row g-4">
<div class="col-12 col-lg-8">
<div class="card">
<div class="card-header bg-white"><h2 class="h6 mb-0" style="font-size:11px;letter-spacing:.06em;text-transform:uppercase"><i class="bi bi-building me-2 text-primary" aria-hidden="true"></i>Room Details</h2></div>
<div class="card-body">
<div class="row g-3" style="font-size:13px">
<div class="col-6"><div class="text-muted" style="font-size:11px;letter-spacing:.06em;text-transform:uppercase">Building</div><div class="fw-medium">{{ $examRoom->building ?? "Not assigned" }}</div></div>
<div class="col-6"><div class="text-muted" style="font-size:11px;letter-spacing:.06em;text-transform:uppercase">Capacity</div><div><span class="badge bg-light text-dark border status-badge"><i class="bi bi-people me-1" aria-hidden="true"></i> {{ $examRoom->capacity ?? "Not specified" }}</span></div></div>
<div class="col-12"><div class="text-muted" style="font-size:11px;letter-spacing:.06em;text-transform:uppercase">Camera Position Notes</div><div class="text-muted" style="font-size:13px;white-space:pre-wrap">{{ $examRoom->camera_position_notes ?: "No notes provided." }}</div></div>
</div>
</div>
</div>
</div>
<div class="col-12 col-lg-4">
<div class="card">
<div class="card-header bg-white"><h2 class="h6 mb-0" style="font-size:11px;letter-spacing:.06em;text-transform:uppercase">Info</h2></div>
<div class="card-body" style="font-size:13px">
<div class="d-flex justify-content-between mb-2"><span class="text-muted">ID</span><code class="text-mono" style="font-size:11px">{{ Str::limit($examRoom->id,12) }}</code></div>
<div class="d-flex justify-content-between mb-2"><span class="text-muted">Created</span><span>{{ $examRoom->created_at?->format('Y-m-d H:i') ?? "—" }}</span></div>
<div class="d-flex justify-content-between"><span class="text-muted">Updated</span><span>{{ $examRoom->updated_at?->format('Y-m-d H:i') ?? "—" }}</span></div>
</div>
</div>
</div>
</div>
@push("scripts")
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>document.querySelectorAll(".delete-form").forEach(f=>f.addEventListener("submit",e=>{e.preventDefault();Swal.fire({title:"Delete room?",text:"This will soft delete the room.",icon:"warning",showCancelButton:true,confirmButtonColor:"#DC2626",confirmButtonText:"Delete"}).then(r=>{if(r.isConfirmed)f.submit()})}))</script>
@endpush
@endsection
