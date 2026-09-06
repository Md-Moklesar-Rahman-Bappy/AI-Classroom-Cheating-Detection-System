@extends("layouts.bootstrap")
@section("title","Events")
@section("content")
<div class="d-flex justify-content-between align-items-center mb-4">
    <div><h2 class="mb-1" style="font-weight:700;">Detection Events</h2><p class="text-muted mb-0" style="font-size:13px;">11-event taxonomy — D1/D2/D3 · B1-B5 · S1-S3 — text plus color</p></div>
    <span class="badge bg-dark status-badge"><i class="bi bi-activity me-1"></i> {{ $events->total() }} total</span>
</div>

<div class="card p-3 mb-4">
    <form method="GET" class="row g-3 align-items-end">
        <div class="col-12 col-md-3"><label class="form-label" style="font-size:12px;letter-spacing:0.06em;text-transform:uppercase;">Event Code</label><select name="event_type" class="form-select"><option value="">All codes</option><option value="D1" @selected(request('event_type')=='D1')>D1 Person</option><option value="D2" @selected(request('event_type')=='D2')>D2 Phone</option><option value="D3" @selected(request('event_type')=='D3')>D3 Multiple</option><option value="B1" @selected(request('event_type')=='B1')>B1 Left</option><option value="B2" @selected(request('event_type')=='B2')>B2 Right</option><option value="B3" @selected(request('event_type')=='B3')>B3 Back</option><option value="B4" @selected(request('event_type')=='B4')>B4 Departure</option><option value="B5" @selected(request('event_type')=='B5')>B5 Head Movement</option><option value="S1" @selected(request('event_type')=='S1')>S1 Normal</option><option value="S2" @selected(request('event_type')=='S2')>S2 Insufficient</option><option value="S3" @selected(request('event_type')=='S3')>S3 Tracking Lost</option></select></div>
        <div class="col-12 col-md-2"><label class="form-label" style="font-size:12px;letter-spacing:0.06em;text-transform:uppercase;">Category</label><select name="event_category" class="form-select"><option value="">All</option><option value="detection" @selected(request('event_category')=='detection')>Detection</option><option value="behavior" @selected(request('event_category')=='behavior')>Behavior</option><option value="system" @selected(request('event_category')=='system')>System</option></select></div>
        <div class="col-12 col-md-2"><label class="form-label" style="font-size:12px;letter-spacing:0.06em;text-transform:uppercase;">Track ID</label><input type="number" name="track_id" class="form-control" placeholder="e.g. 4" value="{{ request('track_id') }}"></div>
        <div class="col-12 col-md-2"><label class="form-label" style="font-size:12px;letter-spacing:0.06em;text-transform:uppercase;">Review Status</label><select name="review_status" class="form-select"><option value="">All reviews</option><option value="pending" @selected(request('review_status')=='pending')>pending</option><option value="confirmed_suspicious" @selected(request('review_status')=='confirmed_suspicious')>confirmed</option><option value="dismissed_normal" @selected(request('review_status')=='dismissed_normal')>dismissed</option><option value="needs_further_review" @selected(request('review_status')=='needs_further_review')>needs review</option></select></div>
        <div class="col-12 col-md-3 d-flex gap-2"><button class="btn btn-primary"><i class="bi bi-funnel me-1"></i> Filter</button><a href="{{ route("detection-events.index") }}" class="btn btn-outline-secondary">Reset</a></div>
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
                <thead><tr><th>Type</th><th>Track</th><th>Time</th><th>Frame</th><th>Review</th><th>Confidence</th><th>Actions</th></tr></thead>
                <tbody>
                    @foreach($events as $e)
                    <tr>
                        <td>
                            @php $code=$e->event_type; $catMap=['D1'=>'detection','D2'=>'detection','D3'=>'detection','B1'=>'behavior','B2'=>'behavior','B3'=>'behavior','B4'=>'behavior','B5'=>'behavior','S1'=>'system','S2'=>'system','S3'=>'system']; $cat=$catMap[$code]??'unknown'; @endphp
                            <span class="badge @if(str_starts_with($code,'D') && $code=='D2') bg-primary @elseif($code=='D3') bg-warning text-dark @elseif(str_starts_with($code,'D')) bg-success @elseif($code=='B4') bg-danger @elseif(str_starts_with($code,'S')) bg-secondary @else bg-warning text-dark @endif status-badge">{{ $code }}</span>
                            <span class="badge bg-light text-dark border" style="font-size:10px;">{{ $cat }}</span>
                        </td>
                        <td><span class="badge bg-dark status-badge"><i class="bi bi-bullseye me-1"></i> ID:{{ $e->temporary_track_id }}</span></td>
                        <td style="font-variant-numeric:tabular-nums;">{{ $e->started_at_seconds !== null ? number_format($e->started_at_seconds,1).'s' : '—' }}</td>
                        <td style="font-variant-numeric:tabular-nums;">{{ $e->started_at_frame ?? '—' }}</td>
                        <td><span class="badge @if($e->review_status=="pending") bg-warning text-dark @elseif($e->review_status=="confirmed_suspicious") bg-danger @elseif($e->review_status=="dismissed_normal") bg-success @else bg-info @endif status-badge">{{ $e->review_status }}</span></td>
                        <td><span style="font-variant-numeric:tabular-nums;">{{ $e->confidence ?? $e->rule_score ?? "—" }}</span></td>
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
