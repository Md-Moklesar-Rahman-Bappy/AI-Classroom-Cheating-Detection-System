@props(['action' => null, 'label' => 'items'])

@if(auth()->user()->hasAnyRole(['system_admin','exam_admin']))
<form id="bulkDeleteForm" method="POST" action="{{ $action }}" class="d-none card card-body p-2 mb-3 flex-row justify-content-between align-items-center gap-2">
    @csrf
    <span class="small"><strong id="bulkCount">0</strong> {{ $label }} selected</span>
    <a href="#" onclick="clearBulkSelection(); return false;" class="btn btn-sm btn-outline-secondary">Clear selection</a>
    <div id="bulkIds"></div>
    <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirmBulk('Move to Trash?', 'These {{ $label }} will be moved to Trash. You can restore them later.', '#DC2626', 'Move to Trash')"><i class="bi bi-trash me-1" aria-hidden="true"></i> Delete selected</button>
</form>

@push('scripts')
<script>
(function(){
    const checks = () => [...document.querySelectorAll('.row-check')];
    const selectAll = () => [...document.querySelectorAll('.select-all')];
    const bulkForm = document.getElementById('bulkDeleteForm');
    const bulkCount = document.getElementById('bulkCount');
    const bulkIds = document.getElementById('bulkIds');
    function refreshBulk(){
        const ids = checks().filter(c => c.checked).map(c => c.value);
        const allCheckedOnPage = checks().length > 0 && ids.length === checks().length;
        selectAll().forEach(s => s.checked = allCheckedOnPage);
        selectAll().forEach(s => s.indeterminate = checks().some(c => c.checked) && !allCheckedOnPage);
        if (!bulkForm) return;
        bulkForm.classList.toggle('d-none', ids.length === 0);
        bulkCount.textContent = ids.length;
        bulkIds.innerHTML = ids.map(id => '<input type="hidden" name="ids[]" value="'+id+'">').join('');
    }
    function clearBulkSelection(){
        checks().forEach(c => c.checked = false);
        refreshBulk();
    }
    window.clearBulkSelection = clearBulkSelection;
    document.addEventListener('DOMContentLoaded', () => {
        selectAll().forEach(s => s.addEventListener('change', () => {
            checks().forEach(c => c.checked = s.checked);
            refreshBulk();
        }));
        checks().forEach(c => c.addEventListener('change', () => {
            const ids = checks().filter(x => x.checked).map(x => x.value);
            checks().forEach(other => { if (other.value === c.value) other.checked = c.checked; });
            refreshBulk();
        }));
        refreshBulk();
        if (bulkForm) bulkForm.addEventListener('submit', e => {
            const ids = checks().filter(c => c.checked).map(c => c.value);
            if (!ids.length) { e.preventDefault(); return; }
            e.preventDefault();
        });
    });
})();
window.confirmBulk = function(title, text, color, btnText) {
    const ids = [...document.querySelectorAll('.row-check')].filter(c => c.checked).map(c => c.value);
    if (!ids.length) return false;
    Swal.fire({ title: title, html: '<b>' + ids.length + '</b> selected. ' + text, icon: 'warning', showCancelButton: true, confirmButtonColor: color, confirmButtonText: btnText }).then(r => {
        if (r.isConfirmed) {
            document.getElementById('bulkIds').innerHTML = ids.map(id => '<input type="hidden" name="ids[]" value="' + id + '">').join('');
            document.getElementById('bulkDeleteForm').submit();
        }
    });
    return false;
};
</script>
@endpush
@endif
