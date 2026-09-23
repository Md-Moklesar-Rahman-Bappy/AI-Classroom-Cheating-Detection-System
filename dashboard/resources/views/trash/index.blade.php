@extends("layouts.bootstrap")
@section("title","Trash")
@section("content")
<div class="d-flex justify-content-between align-items-center mb-4">
    <div><h2 class="mb-1" style="font-weight:700;">Global Trash</h2><p class="text-muted mb-0" style="font-size:13px;">Soft-deleted items — restore or permanent delete — audited</p></div>
    <span class="badge bg-warning text-dark">Trash</span>
</div>

@php($trashCanRestore = auth()->user()->hasAnyRole(['system_admin','exam_admin']))
@php($trashCanForce = auth()->user()->hasAnyRole(['system_admin']))

@php
$groups = [
    'Exam Rooms' => ['items'=> App\Models\ExamRoom::onlyTrashed()->latest()->get(), 'type'=>'ExamRoom'],
    'Exam Sessions' => ['items'=> App\Models\ExamSession::onlyTrashed()->latest()->get(), 'type'=>'ExamSession'],
    'Cameras' => ['items'=> App\Models\CameraSource::onlyTrashed()->latest()->get(), 'type'=>'CameraSource'],
    'Video Assets' => ['items'=> App\Models\VideoAsset::onlyTrashed()->latest()->get(), 'type'=>'VideoAsset'],
    'Analysis Jobs' => ['items'=> App\Models\AnalysisJob::onlyTrashed()->latest()->get(), 'type'=>'AnalysisJob'],
    'Detection Events' => ['items'=> App\Models\DetectionEvent::onlyTrashed()->latest()->get(), 'type'=>'DetectionEvent'],
    'Evidence' => ['items'=> App\Models\EventEvidence::onlyTrashed()->latest()->get(), 'type'=>'EventEvidence'],
    'Users' => ['items'=> App\Models\User::onlyTrashed()->latest()->get(), 'type'=>'User'],
    'Model Versions' => ['items'=> App\Models\ModelVersion::onlyTrashed()->latest()->get(), 'type'=>'ModelVersion'],
];
@endphp

@if($trashCanRestore || $trashCanForce)
<form id="trashBulkForm" method="POST" action="" class="card card-body p-2 mb-3 d-none flex-wrap gap-2 align-items-center" onsubmit="event.preventDefault(); handleTrashBulk();">
    @csrf
    <div id="trashBulkIds"></div>
    <span class="small"><strong id="trashSelectedCount">0</strong> selected on this page</span>
    <button type="button" onclick="setTrashBulkAction('restore')" class="btn btn-sm btn-success">Restore selected</button>
    @if($trashCanForce)<button type="button" onclick="setTrashBulkAction('force')" class="btn btn-sm btn-danger">Delete permanently</button>@endif
</form>
@endif

@foreach($groups as $label => $group)
@php $items = $group['items']; $typeName = $group['type']; @endphp
<div class="card mb-3">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0" style="font-size:13px;letter-spacing:.06em;text-transform:uppercase;">{{ $label }} — <span class="badge bg-dark status-badge">{{ $items->count() }}</span></h5>
    </div>
    <div class="card-body p-0">
        @if($items->isEmpty())<div class="p-3 text-muted" style="font-size:13px;">No trashed {{ strtolower($label) }}</div>
        @else
        <div class="table-responsive">
            <table class="table table-sm mb-0" style="font-size:13px;">
                <thead><tr>
                    @if($trashCanRestore || $trashCanForce)<th style="width:44px"><input type="checkbox" class="trash-select-all" aria-label="Select all {{ $label }} on this page"></th>@endif
                    <th style="width:40px">SL</th><th>Name / Info</th><th>Deleted At</th><th>Actions</th>
                </tr></thead>
                <tbody>
                    @foreach($items as $i => $item)
                    <tr>
                        @if($trashCanRestore || $trashCanForce)
                        <td><input type="checkbox" class="trash-select" value='{"type":"{{ $typeName }}","id":{{ $item->id }}}' aria-label="Select {{ strtolower($label) }} {{ $items->firstItem() + $i }}"></td>
                        @endif
                        <td class="text-muted" style="font-variant-numeric:tabular-nums">{{ $items->firstItem() + $i }}</td>
                        <td>{{ $item->name ?? $item->original_filename ?? $item->event_type ?? $item->email ?? ($item->version ?? $item->identifier ?? '—') }}</td>
                        <td class="text-muted" style="font-size:11px">{{ $item->deleted_at ? $item->deleted_at->format('Y-m-d H:i') : '—' }}</td>
                        <td>
                            @if($trashCanRestore)
                            <form method="POST" action="{{ route('trash.restore', $item->id) }}?type={{ $typeName }}" class="d-inline restore-form">@csrf<button class="btn btn-sm btn-success" aria-label="Restore item">Restore</button></form>
                            @endif
                            @if($trashCanForce)
                            <form method="POST" action="{{ route('trash.force', $item->id) }}?type={{ $typeName }}" class="d-inline force-form">@csrf<button class="btn btn-sm btn-danger" aria-label="Delete permanently" onclick="event.preventDefault(); const btn=this; Swal.fire({icon:'warning',title:'Permanently delete?',text:'This CANNOT be undone.',showCancelButton:true,confirmButtonColor:'#dc2626',confirmButtonText:'Delete forever'}).then(r=>{if(r.isConfirmed)btn.closest('form').submit()});">Delete</button></form>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
</div>
@endforeach

@push("scripts")
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
const trashChecks = [...document.querySelectorAll('.trash-select')];
const trashSelectAll = [...document.querySelectorAll('.trash-select-all')];
const trashBulkForm = document.getElementById('trashBulkForm');
function updateTrashSelection(){
    const selected = trashChecks.filter(c=>c.checked).map(c=>JSON.parse(c.value));
    trashSelectAll.forEach(s=>s.checked = trashChecks.length>0 && selected.length === trashChecks.length);
    if(trashBulkForm){
        trashBulkForm.classList.toggle('d-none', selected.length===0);
        document.getElementById('trashSelectedCount').textContent = selected.length;
        document.getElementById('trashBulkIds').replaceChildren(...selected.map(it=>{const i=document.createElement('input');i.type='hidden';i.name='trash_items[][type]';i.value=it.type;const i2=document.createElement('input');i2.type='hidden';i2.name='trash_items[][id]';i2.value=it.id;return [i,i2];}).flat());
    }
}
trashChecks.forEach(c=>c.addEventListener('change',()=>{
    trashChecks.forEach(s=>{if(s!==c) s.checked=c.checked;});
    updateTrashSelection();
}));
trashSelectAll.forEach(s=>s.addEventListener('change',()=>{trashChecks.forEach(c=>c.checked=s.checked);updateTrashSelection();}));
function setTrashBulkAction(action){
    const selected = trashChecks.filter(c=>c.checked).map(c=>JSON.parse(c.value));
    if(action==='restore') trashBulkForm.action = "{{ route('trash.bulk-restore') }}";
    else trashBulkForm.action = "{{ route('trash.bulk-force') }}";
    trashBulkForm.submit();
}
</script>
@endpush
@endsection
