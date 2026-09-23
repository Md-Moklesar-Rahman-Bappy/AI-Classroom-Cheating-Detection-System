@extends("layouts.bootstrap")
@section("title","Evidence Gallery")
@section("content")
<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
    <div><h2 class="mb-1" style="font-weight:700;">Evidence Gallery</h2><p class="text-muted mb-0" style="font-size:13px;">Global gallery — {{ $evidences->total() }} evidences — protected, authorized access</p></div>
    <div class="d-flex gap-2"><a href="{{ route('evidence.index') }}" class="btn btn-sm btn-outline-secondary @if(!request()->boolean('trashed')) active @endif">Active</a><a href="{{ route('evidence.index', ['trashed'=>1]) }}" class="btn btn-sm btn-outline-secondary @if(request()->boolean('trashed')) active @endif">Trashed</a></div>
</div>
<div class="card p-3 mb-4">
    <form method="GET" class="row g-3 align-items-end">
        <div class="col-12 col-md-3"><label class="form-label" style="font-size:11px;letter-spacing:.06em;text-transform:uppercase;">Event Code</label><select name="event_type" class="form-select"><option value="">All codes</option>@foreach(['D1','D2','D3','B1','B2','B3','B4','B5','S1','S2','S3'] as $c)<option value="{{ $c }}" @selected(request('event_type')==$c)>{{ $c }}</option>@endforeach</select></div>
        <div class="col-12 col-md-2"><label class="form-label" style="font-size:11px;letter-spacing:.06em;text-transform:uppercase;">File Type</label><select name="file_type" class="form-select"><option value="">All</option><option value="snapshot" @selected(request('file_type')=='snapshot')>snapshot</option><option value="clip" @selected(request('file_type')=='clip')>clip</option></select></div>
        <div class="col-12 col-md-3"><label class="form-label" style="font-size:11px;letter-spacing:.06em;text-transform:uppercase;">Search</label><input type="text" name="search" class="form-control" placeholder="file path" value="{{ request('search') }}"></div>
        <div class="col-12 col-md-4 d-flex gap-2"><button class="btn btn-primary">Filter</button><a href="{{ route('evidence.index') }}" class="btn btn-outline-secondary">Reset</a></div>
    </form>
</div>
@if($evidences->isEmpty())
    <div class="card p-5 text-center"><i class="bi bi-image text-muted" style="font-size:32px;"></i><p class="text-muted mt-2" style="font-size:13px;">No evidence found</p></div>
@else
    @php($canManageEvidence = auth()->user()->hasAnyRole(['system_admin', 'exam_admin']))
    @if($canManageEvidence)
    <form method="POST" action="{{ request()->boolean('trashed') ? route('evidence.bulk-restore') : route('evidence.bulk-delete') }}" id="bulkForm">@csrf<div id="evidenceBulkIds"></div></form>
    <div id="evidenceBulkBar" class="card card-body p-2 mb-3 d-none flex-row justify-content-between align-items-center gap-2">
        <span class="small"><strong id="evidenceSelectedCount">0</strong> selected on this page</span>
        <button type="submit" form="bulkForm" class="btn btn-sm {{ request()->boolean('trashed') ? 'btn-success' : 'btn-danger' }}">@if(request()->boolean('trashed')) Restore selected @else Delete selected @endif</button>
    </div>
    <div class="d-flex gap-2 align-items-center mb-3"><input type="checkbox" id="selectAll"><label for="selectAll" style="font-size:13px;">Select all on this page</label></div>
    @endif
        <div class="row g-3">
            @foreach($evidences as $i => $ev)
            <div class="col-12 col-md-6 col-lg-4 col-xl-3">
                <div class="card h-100">
                    <div class="d-flex justify-content-between align-items-center p-2">
                        <div class="d-flex gap-1 align-items-center"><span class="badge bg-light text-dark border" style="font-size:10px">{{ $evidences->firstItem()+$i }}</span>@if($canManageEvidence)<input class="evidence-select" type="checkbox" value="{{ $ev->id }}" aria-label="Select evidence {{ $evidences->firstItem()+$i }}">@endif</div>
                        <span class="badge bg-primary" style="font-size:10px;">{{ $ev->file_type }}</span>
                        @if($ev->event)<span class="badge bg-dark" style="font-size:10px;">{{ $ev->event->event_type }} #{{ $ev->event->temporary_track_id }}</span>@endif
                    </div>
                    <a href="{{ route('evidence.show', $ev) }}" target="_blank" class="d-block bg-light d-flex align-items-center justify-content-center" style="height:160px;overflow:hidden;">
                        <img src="{{ route('evidence.show', $ev) }}" alt="Evidence snapshot" style="width:100%;height:160px;object-fit:cover;" loading="lazy" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                        <div style="display:none;height:160px;align-items:center;justify-content:center;"><i class="bi bi-image text-muted" style="font-size:32px;"></i></div>
                    </a>
                    <div class="card-body p-2" style="font-size:12px;">
                        <div><strong>Snapshot</strong> <span class="text-muted">Frame {{ $ev->frame_number ?? '—' }}</span></div>
                        <div class="text-muted text-monospace" style="font-size:11px;">{{ Str::limit($ev->file_path,30) }}</div>
                        <div class="text-muted" style="font-size:11px;">{{ $ev->width }}x{{ $ev->height }} @ {{ number_format($ev->captured_at_seconds ?? 0,1) }}s</div>
                    </div>
                    <div class="card-footer bg-white d-flex gap-1 p-2">
                        <a href="{{ route('evidence.show', $ev) }}" class="btn btn-sm btn-outline-primary" style="font-size:11px;">Preview</a>
                        <a href="{{ route('evidence.download', $ev) }}?format=jpg" class="btn btn-sm btn-outline-secondary" style="font-size:11px;">JPG</a>
                        <a href="{{ route('evidence.download', $ev) }}?format=json" class="btn btn-sm btn-outline-dark" style="font-size:11px;">JSON</a>
                        @if($canManageEvidence)
                            @if(request()->boolean('trashed'))
                            <form method="POST" action="{{ route('evidence.restore', $ev->id) }}" class="ms-auto">@csrf<button class="btn btn-sm btn-success" aria-label="Restore evidence"><i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i></button></form>
                            @else
                            <form method="POST" action="{{ route('evidence.destroy', $ev) }}" class="evidence-delete-form ms-auto">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger" aria-label="Move evidence to Trash"><i class="bi bi-trash" aria-hidden="true"></i></button></form>
                            @endif
                        @endif
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    <div class="mt-3"><x-pagination :paginator="$evidences" /></div>
@endif
@push("scripts")
<script>
const evidenceSelectAll=document.getElementById('selectAll');
const evidenceChecks=[...document.querySelectorAll('.evidence-select')];
const evidenceBulkBar=document.getElementById('evidenceBulkBar');
function updateEvidenceSelection(){
    const selected=[...new Set(evidenceChecks.filter(check=>check.checked).map(check=>check.value))];
    if(evidenceSelectAll)evidenceSelectAll.checked=selected.length>0&&selected.length===evidenceChecks.length;
    if(!evidenceBulkBar)return;
    evidenceBulkBar.classList.toggle('d-none',selected.length===0);
    document.getElementById('evidenceSelectedCount').textContent=selected.length;
    const hidden=document.getElementById('evidenceBulkIds');
    hidden.replaceChildren(...selected.map(id=>{const input=document.createElement('input');input.type='hidden';input.name='ids[]';input.value=id;return input}));
}
evidenceSelectAll?.addEventListener('change',()=>{evidenceChecks.forEach(check=>check.checked=evidenceSelectAll.checked);updateEvidenceSelection()});
evidenceChecks.forEach(check=>check.addEventListener('change',updateEvidenceSelection));
document.querySelectorAll('.evidence-delete-form').forEach(form=>form.addEventListener('submit',event=>{
    event.preventDefault();
    Swal.fire({title:'Move evidence to Trash?',text:'This item can be restored later.',icon:'warning',showCancelButton:true,confirmButtonColor:'#dc2626',confirmButtonText:'Move to Trash'}).then(result=>{if(result.isConfirmed)form.submit()});
}));
document.getElementById('bulkForm')?.addEventListener('submit',event=>{
    if(@json(request()->boolean('trashed')))return;
    event.preventDefault();
    const count=new Set(evidenceChecks.filter(check=>check.checked).map(check=>check.value)).size;
    Swal.fire({title:`Move ${count} evidence item(s) to Trash?`,text:'These items can be restored later.',icon:'warning',showCancelButton:true,confirmButtonColor:'#dc2626',confirmButtonText:'Move to Trash'}).then(result=>{if(result.isConfirmed)document.getElementById('bulkForm').submit()});
});
</script>
@endpush
@endsection
