@extends("layouts.bootstrap")
@section("title","Trash")
@section("content")
<div class="d-flex justify-content-between align-items-center mb-4">
    <div><h2 class="mb-1" style="font-weight:700;">Global Trash</h2><p class="text-muted mb-0" style="font-size:13px;">Soft-deleted items — restore or permanent delete — audited</p></div>
    <span class="badge bg-warning text-dark">Trash</span>
</div>

@php $trashCanRestore = auth()->user()->hasAnyRole(['system_admin','exam_admin']); @endphp
@php $trashCanForce = auth()->user()->hasAnyRole(['system_admin']); @endphp

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

@foreach($groups as $label => $group)
@php $items = $group['items']; $typeName = $group['type']; $sectionId = 'trash-section-'.Str::slug($typeName); @endphp
<div class="card mb-3" id="{{ $sectionId }}" data-trash-section="{{ Str::slug($typeName) }}">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0" style="font-size:13px;letter-spacing:.06em;text-transform:uppercase;">{{ $label }} — <span class="badge bg-dark status-badge">{{ $items->count() }}</span></h5>
    </div>
    <div class="card-body p-0">
        @if($items->isEmpty())
            <div class="p-3 text-muted" style="font-size:13px;">No trashed {{ strtolower($label) }}</div>
        @else
        @if($trashCanRestore || $trashCanForce)
        <div class="p-2 d-flex justify-content-between align-items-center gap-2 border-bottom" data-trash-toolbar="{{ Str::slug($typeName) }}">
            <span class="small"><strong class="trash-selected-count" data-trash-count>0</strong> selected</span>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-sm btn-success" onclick="submitSectionAction('restore', '{{ Str::slug($typeName) }}')" disabled data-trash-btn="restore">Restore selected</button>
                @if($trashCanForce)
                <button type="button" class="btn btn-sm btn-danger" onclick="submitSectionAction('force', '{{ Str::slug($typeName) }}')" disabled data-trash-btn="force">Delete permanently</button>
                @endif
                <a href="#" onclick="clearSectionSelection('{{ Str::slug($typeName) }}'); return false;" class="btn btn-sm btn-outline-secondary">Clear</a>
            </div>
        </div>
        @endif
        <div class="table-responsive">
            <table class="table table-sm mb-0" style="font-size:13px;">
                <thead><tr>
                    @if($trashCanRestore || $trashCanForce)
                    <th style="width:44px"><input type="checkbox" class="trash-select-all" data-trash-select-all="{{ Str::slug($typeName) }}" aria-label="Select all {{ $label }} on this page" onclick="selectAllSection('{{ Str::slug($typeName) }}', this)"></th>
                    @endif
                    <th style="width:40px">SL</th><th>Name / Info</th><th>Deleted At</th><th>Actions</th>
                </tr></thead>
                <tbody>
                    @foreach($items as $i => $item)
                    <tr data-trash-item-id="{{ $item->id }}" data-trash-type="{{ Str::slug($typeName) }}">
                        @if($trashCanRestore || $trashCanForce)
                        <td><input type="checkbox" class="trash-item-check" data-trash-item-checkbox="{{ Str::slug($typeName) }}" value="{{ $item->id }}" aria-label="Select {{ strtolower($label) }} {{ $i + 1 }}" onclick="updateSectionSelection('{{ Str::slug($typeName) }}')"></td>
                        @endif
                        <td class="text-muted" style="font-variant-numeric:tabular-nums">{{ $i + 1 }}</td>
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
    @if($trashCanRestore || $trashCanForce)
    <form id="trash-form-{{ Str::slug($typeName) }}-restore" method="POST" action="{{ route('trash.bulk-restore') }}" class="d-none">
        @csrf
        <div data-trash-restore-hiddens="{{ Str::slug($typeName) }}"></div>
        <input type="hidden" name="confirmation" value="RESTORE SELECTED">
    </form>
    <form id="trash-form-{{ Str::slug($typeName) }}-force" method="POST" action="{{ route('trash.bulk-force') }}" class="d-none">
        @csrf
        <div data-trash-force-hiddens="{{ Str::slug($typeName) }}"></div>
        <input type="hidden" name="confirmation" value="DELETE PERMANENTLY">
    </form>
    @endif
</div>
@endforeach

@push("scripts")
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
function getSection(slug) {
    return document.getElementById('trash-section-'+slug);
}
function selectAllSection(slug, checkbox) {
    const section = getSection(slug);
    if (!section) return;
    const items = section.querySelectorAll('.trash-item-check');
    items.forEach(c => c.checked = checkbox.checked);
    updateSectionSelection(slug);
}
function clearSectionSelection(slug) {
    const section = getSection(slug);
    if (!section) return;
    section.querySelectorAll('.trash-item-check').forEach(c => c.checked = false);
    updateSectionSelection(slug);
}
function updateSectionSelection(slug) {
    const section = getSection(slug);
    if (!section) return;
    const checks = section.querySelectorAll('.trash-item-check');
    const selected = [...checks].filter(c => c.checked);
    const count = selected.length;
    const toolbar = section.querySelector('[data-trash-toolbar="'+slug+'"]');
    if (toolbar) {
        toolbar.querySelector('.trash-selected-count')?.setAttribute('data-trash-count', count);
        toolbar.querySelector('.trash-selected-count').textContent = count;
        toolbar.querySelector('[data-trash-btn="restore"]').disabled = count === 0;
        toolbar.querySelector('[data-trash-btn="force"]').disabled = count === 0;
    }
    // sync hidden inputs into correct form only
    const typeName = section.getAttribute('data-trash-section');
    const restoreForm = document.getElementById('trash-form-'+slug+'-restore');
    const forceForm = document.getElementById('trash-form-'+slug+'-force');
    const restoreHidden = restoreForm ? restoreForm.querySelector('[data-trash-restore-hiddens="'+slug+'"]') : null;
    const forceHidden = forceForm ? forceForm.querySelector('[data-trash-force-hiddens="'+slug+'"]') : null;
    const ids = selected.map(c => c.value);
    if (restoreHidden) {
        restoreHidden.innerHTML = '';
        ids.forEach(id => {
            const i1 = document.createElement('input'); i1.type = 'hidden'; i1.name = 'ids[]'; i1.value = id; restoreHidden.appendChild(i1);
        });
        const t1 = document.createElement('input'); t1.type = 'hidden'; t1.name = 'type'; t1.value = typeName; restoreHidden.appendChild(t1);
    }
    if (forceHidden) {
        forceHidden.innerHTML = '';
        ids.forEach(id => {
            const i1 = document.createElement('input'); i1.type = 'hidden'; i1.name = 'ids[]'; i1.value = id; forceHidden.appendChild(i1);
        });
        const t2 = document.createElement('input'); t2.type = 'hidden'; t2.name = 'type'; t2.value = typeName; forceHidden.appendChild(t2);
    }
}
function submitSectionAction(action, slug) {
    const section = getSection(slug);
    if (!section) return;
    const checks = section.querySelectorAll('.trash-item-check');
    const selected = [...checks].filter(c => c.checked);
    if (selected.length === 0) { alert('No selection'); return; }
    const ids = selected.map(c => c.value);
    const typeName = section.getAttribute('data-trash-section');
    if (action === 'restore') {
        const form = document.getElementById('trash-form-'+slug+'-restore');
        if (!form) return;
        // ensure hidden inputs already synced by updateSectionSelection
        Swal.fire({icon:'warning',title:'Restore selected?',text:ids.length+' items will be restored.',showCancelButton:true,confirmButtonColor:'#198754',confirmButtonText:'Restore'}).then(r=>{if(r.isConfirmed)form.submit();});
    } else {
        const form = document.getElementById('trash-form-'+slug+'-force');
        if (!form) return;
        Swal.fire({icon:'warning',title:'Permanently delete?',text:ids.length+' items will be permanently deleted. This CANNOT be undone.',showCancelButton:true,confirmButtonColor:'#dc2626',confirmButtonText:'Delete forever',input:'text',inputPlaceholder:'DELETE PERMANENTLY',inputValidator:(v)=>v!=='DELETE PERMANENTLY'?'Type DELETE PERMANENTLY to confirm.':''}).then(r=>{if(r.isConfirmed){const hidden=form.querySelector('[data-trash-force-hiddens="'+slug+'"]');if(hidden){const conf=document.createElement('input');conf.type='hidden';conf.name='confirmation';conf.value='DELETE PERMANENTLY';hidden.appendChild(conf);}form.submit();}});
    }
}
</script>
@endpush
