@extends("layouts.bootstrap")
@section("title","Audit Logs")
@section("content")
<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
<div><h1 class="h4 mb-1" style="font-weight:700;letter-spacing:-.02em">Audit Logs</h1><p class="text-muted mb-0" style="font-size:13px">Immutable trail — {{ $logs->total() }} entries, correlation IDs, actor + result</p></div>
<span class="badge bg-light text-dark border status-badge"><i class="bi bi-shield-check me-1" aria-hidden="true"></i> Audited</span>
</div>

<div class="card p-3 mb-4">
<form method="GET" action="{{ route("audit-logs.index") }}" class="row g-3" role="search" aria-label="Filter audit logs">
<div class="col-12 col-md-3">
<label for="action" class="form-label" style="font-size:11px;letter-spacing:.06em;text-transform:uppercase;color:var(--color-text-muted);font-weight:600">Action</label>
<input id="action" type="text" name="action" value="{{ request('action') }}" class="form-control focus-ring" placeholder="e.g., login, create" style="font-size:13px">
</div>
<div class="col-6 col-md-2">
<label for="result" class="form-label" style="font-size:11px;letter-spacing:.06em;text-transform:uppercase;color:var(--color-text-muted);font-weight:600">Result</label>
<select id="result" name="result" class="form-select focus-ring" style="font-size:13px">
<option value="">All</option>
<option value="success" @selected(request('result')=='success')>success</option>
<option value="failure" @selected(request('result')=='failure')>failure</option>
</select>
</div>
<div class="col-6 col-md-3">
<label for="actor" class="form-label" style="font-size:11px;letter-spacing:.06em;text-transform:uppercase;color:var(--color-text-muted);font-weight:600">Actor</label>
<input id="actor" type="text" name="actor_id" value="{{ request('actor_id') }}" class="form-control focus-ring" placeholder="Actor ID" style="font-size:13px">
</div>
<div class="col-12 col-md-4 d-flex align-items-end gap-2">
<button class="btn btn-primary focus-ring"><i class="bi bi-search me-1" aria-hidden="true"></i> Filter</button>
<a href="{{ route("audit-logs.index") }}" class="btn btn-outline-secondary focus-ring">Clear</a>
</div>
</form>
</div>

@if($logs->isEmpty())
<div class="card empty-state">
<div class="empty-icon"><i class="bi bi-journal-text" aria-hidden="true"></i></div>
<h2 class="h5">No logs</h2>
<p class="text-muted" style="font-size:13px">No audit entries match your filters.</p>
</div>
@else
<div class="card d-none d-md-block">
<div class="table-responsive">
<table class="table table-hover align-middle mb-0" style="font-size:13px">
<caption class="visually-hidden">Audit logs — action, actor, target, result, time</caption>
<thead><tr><th>Action</th><th>Actor</th><th>Target</th><th>Result</th><th>Time</th></tr></thead>
<tbody>
@forelse($logs as $l)
<tr>
<td><span class="badge bg-light text-dark border status-badge">{{ $l->action }}</span></td>
<td><code class="text-mono" style="font-size:11px">{{ $l->actor_id ?? "system" }}</code></td>
<td><span class="badge bg-dark status-badge text-mono" style="font-size:11px">{{ $l->target_type }}:{{ Str::limit($l->target_id,8) }}</span></td>
<td><span class="badge @if($l->result=="success") bg-success @else bg-danger @endif status-badge"><i class="bi @if($l->result=="success") bi-check-circle @else bi-x-circle @endif me-1" aria-hidden="true"></i>{{ $l->result }}</span></td>
<td class="text-muted" style="font-size:12px;white-space:nowrap">{{ $l->created_at }}</td>
</tr>
@empty
<tr><td colspan="5" class="text-center text-muted py-4">No logs.</td></tr>
@endforelse
</tbody>
</table>
</div>
<div class="card-footer bg-white d-flex flex-column flex-sm-row justify-content-between align-items-center gap-2" style="font-size:12px;color:var(--color-text-muted)"><span>Showing {{ $logs->firstItem() }}–{{ $logs->lastItem() }} of {{ $logs->total() }}</span>{{ $logs->links() }}</div>
</div>
<div class="d-md-none">
@foreach($logs as $l)
<div class="card p-3 mb-2">
<div class="d-flex justify-content-between align-items-start gap-2"><span class="badge bg-light text-dark border status-badge">{{ $l->action }}</span><span class="badge @if($l->result=="success") bg-success @else bg-danger @endif status-badge">{{ $l->result }}</span></div>
<div class="text-muted text-mono mt-2" style="font-size:11px">{{ $l->target_type }}:{{ Str::limit($l->target_id,8) }} • {{ $l->actor_id ?? "system" }}</div>
<div class="text-muted" style="font-size:11px">{{ $l->created_at }}</div>
</div>
@endforeach
<div class="d-flex justify-content-center mt-3">{{ $logs->links() }}</div>
</div>
@endif
@endsection
