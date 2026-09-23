@props(['paginator' => null])

@if($paginator && $paginator->total() > 0)
<div class="pagination-bar d-flex flex-column flex-sm-row justify-content-between align-items-center gap-2" style="font-size:12px;color:var(--color-text-muted)">
    <span>Showing {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} of {{ $paginator->total() }} results</span>

    @if($paginator->hasPages())
        {{ $paginator->onEachSide(1)->links('pagination::bootstrap-5') }}
    @endif
</div>
@endif
