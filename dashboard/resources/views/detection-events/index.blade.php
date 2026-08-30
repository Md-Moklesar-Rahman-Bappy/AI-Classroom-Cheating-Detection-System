@extends("layouts.bootstrap")
@section("title","Events")
@section("content")
<div class="d-flex justify-content-between align-items-center mb-4">
    <div><h2 class="mb-1" style="font-weight:700;">Detection Events</h2><p class="text-muted mb-0" style="font-size:13px;">Surveillance feed — text plus color for every status</p></div>
    <span class="badge bg-dark status-badge"><i class="bi bi-activity me-1"></i> {{ $events->total() }} total</span>
</div>

<div class="card p-3 mb-4">
    <form method="GET" class="row g-3 align-items-end">
        <div class="col-12 col-md-4"><label class="form-label" style="font-size:12px;letter-spacing:0.06em;text-transform:uppercase;">Event Type</label><select name="event_type" class="form-select"><option value="">All types</option><option value="D1">D1 Person</option><option value="D2">D2 Phone</option><option value="B1">B1 Left</option><option value="B2">B2 Right</option><option value="B3">B3 Back</option><option value="B4">B4 Leaving</option></select></div>
        <div class="col-12 col-md-4"><label class="form-label" style="font-size:12px;letter-spacing:0.06em;text-transform:uppercase;">Review Status</label><select name="review_status" class="form-select"><option value="">All reviews</option><option value="pending">pending — needs review</option><option value="confirmed_suspicious">confirmed suspicious</option><option value="dismissed_normal">dismissed normal</option><option value="needs_further_review">needs further review</option></select></div>
        <div class="col-12 col-md-4 d-flex gap-2"><button class="btn btn-primary"><i class="bi bi-funnel me-1"></i> Filter</button><a href="{{ route("detection-events.index") }}" class="btn btn-outline-secondary">Reset</a></div>
    </form>
</div>

@if($events->isEmpty())
    <div class="card p-5 text-center">
        <div class="mx-auto mb-3 d-flex align-items-center justify-content-center" style="width:48px;height:48px;background:#f1f5f9;border-radius:12px;"><i class="bi bi-inbox text-muted" style="font-size:20px;"></i></div>
        <h5>No events</h5><p class="text-muted" style="font-size:13px;">No detection events match the current filter. Adjust filters or wait for new analysis jobs.</p>
    </div>
@else
    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0" id="eventsTable" style="font-size:13px;">
                <thead><tr><th style="width:14%;">Type</th><th style="width:10%;">Track</th><th style="width:16%;">Review</th><th>Confidence</th><th style="width:14%;">Actions</th></tr></thead>
                <tbody>
                    @foreach($events as $e)
                    <tr>
                        <td><span class="badge @if($e->event_type=="D2") bg-primary @elseif(str_starts_with($e->event_type,"B")) bg-danger @else bg-success @endif status-badge"><i class="bi @if($e->event_type=="D2") bi-phone @elseif($e->event_type=="B1") bi-arrow-left @elseif($e->event_type=="B2") bi-arrow-right @elseif($e->event_type=="B3") bi-arrow-up @elseif($e->event_type=="B4") bi-box-arrow-right @else bi-person @endif me-1"></i>{{ $e->event_type }}</span> <span class="text-muted" style="font-size:12px;">{{ $e->event_type }}</span></td>
                        <td><span class="badge bg-dark status-badge"><i class="bi bi-bullseye me-1"></i> ID:{{ $e->temporary_track_id }}</span></td>
                        <td><span class="badge @if($e->review_status=="pending") bg-warning text-dark @elseif($e->review_status=="confirmed_suspicious") bg-danger @elseif($e->review_status=="dismissed_normal") bg-success @else bg-info @endif status-badge">{{ $e->review_status }}</span></td>
                        <td><span style="font-variant-numeric:tabular-nums;">{{ $e->confidence ?? $e->rule_score ?? "—" }}</span> @if($e->confidence)<span class="text-muted" style="font-size:11px;">confidence</span>@endif</td>
                        <td><a href="{{ route("detection-events.show",$e) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye me-1"></i> Detail</a></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-white d-flex justify-content-between align-items-center" style="font-size:12px;color:#64748b;"><span>Showing {{ $events->firstItem() }}–{{ $events->lastItem() }} of {{ $events->total() }}</span> {{ $events->links() }}</div>
    </div>
@endif
@endsection
