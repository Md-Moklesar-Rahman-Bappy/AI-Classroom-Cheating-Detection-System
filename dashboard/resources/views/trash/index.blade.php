@extends("layouts.bootstrap")
@section("title","Trash")
@section("content")
<div class="d-flex justify-content-between align-items-center mb-4">
    <div><h2 class="mb-1" style="font-weight:700;">Global Trash</h2><p class="text-muted mb-0" style="font-size:13px;">Soft-deleted items — restore or permanent delete — audited</p></div>
    <span class="badge bg-warning text-dark">Trash</span>
</div>
@php $sections = [['Exam Rooms',$rooms,'exam-rooms.restore'],['Exam Sessions',$sessions,'exam-sessions.restore'],['Cameras',$cameras,'camera-sources.restore'],['Video Assets',$videos,'video-assets.restore'],['Analysis Jobs',$jobs,'analysis-jobs.restore'],['Detection Events',$events,'detection-events.restore'],['Evidence',$evidences,'evidence.restore'],['Users',$users,'users.restore']]; @endphp
@foreach($sections as [$label,$items,$route])
<div class="card mb-3">
    <div class="card-header bg-white d-flex justify-content-between align-items-center"><h5 class="mb-0" style="font-size:13px;letter-spacing:.06em;text-transform:uppercase;">{{ $label }} — {{ $items->count() }} trashed</h5></div>
    <div class="card-body p-0">
        @if($items->isEmpty())<div class="p-3 text-muted" style="font-size:13px;">No trashed {{ strtolower($label) }}</div>
        @else<div class="table-responsive"><table class="table table-sm mb-0" style="font-size:13px;"><thead><tr><th>ID</th><th>Name/Info</th><th>Deleted</th><th>Action</th></tr></thead><tbody>
            @foreach($items as $item)<tr><td>{{ $item->id }}</td><td>{{ $item->name ?? $item->original_filename ?? $item->event_type ?? $item->email ?? $item->id }}</td><td>{{ $item->deleted_at }}</td><td><form method="POST" action="{{ route($route, $item->id) }}" class="d-inline">@csrf<button class="btn btn-sm btn-success">Restore</button></form></td></tr>@endforeach
        </tbody></table></div>@endif
    </div>
</div>
@endforeach
@endsection
