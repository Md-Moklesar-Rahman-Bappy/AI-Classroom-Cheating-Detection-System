@extends("layouts.bootstrap")
@section("title","Dashboard")
@section("content")
<h2>Dashboard Overview</h2>
<div class="row g-3 mb-4">
    <div class="col-md-3"><div class="card p-3"><h5>Rooms</h5><p class="fs-3">{{ $stats["rooms"] }}</p></div></div>
    <div class="col-md-3"><div class="card p-3"><h5>Sessions</h5><p class="fs-3">{{ $stats["sessions"] }}</p></div></div>
    <div class="col-md-3"><div class="card p-3"><h5>Jobs</h5><p class="fs-3">{{ $stats["jobs"] }}</p></div></div>
    <div class="col-md-3"><div class="card p-3"><h5>Events</h5><p class="fs-3">{{ $stats["events"] }}</p></div></div>
</div>
<div class="card p-3">
    <h5>Recent Activity</h5>
    <p class="text-muted">System operational. Use navigation to manage rooms, sessions, cameras, videos, jobs, events, and audit logs.</p>
    <div class="alert alert-info">Statuses use text plus color: <span class="badge bg-success">Normal</span> <span class="badge bg-warning text-dark">Pending</span> <span class="badge bg-danger">Suspicious</span> <span class="badge bg-secondary">Insufficient</span></div>
</div>
@endsection
