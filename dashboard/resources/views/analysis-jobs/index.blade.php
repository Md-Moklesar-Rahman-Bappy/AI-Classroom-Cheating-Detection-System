@extends("layouts.bootstrap")
@section("title","Analysis Jobs")
@section("content")
<div class="d-flex justify-content-between mb-3"><h2>Analysis Jobs</h2><a href="{{ route("analysis-jobs.create") }}" class="btn btn-primary">New Job</a></div>
@if($jobs->isEmpty())<div class="card p-4 text-center text-muted">No jobs.</div>
@else<div class="table-responsive"><table class="table"><thead><tr><th>ID</th><th>Session</th><th>Status</th><th>Progress</th><th>Actions</th></tr></thead><tbody>@foreach($jobs as $j)<tr><td>{{ Str::limit($j->id,8) }}</td><td>{{ $j->session->name ?? "—" }}</td><td><span class="badge @if($j->status=="completed") bg-success @elseif($j->status=="failed") bg-danger @elseif($j->status=="processing") bg-primary @else bg-warning text-dark @endif">{{ $j->status }}</span></td><td>{{ $j->progress_percent }}%</td><td><a href="{{ route("analysis-jobs.show",$j) }}" class="btn btn-sm btn-outline-primary">View</a></td></tr>@endforeach</tbody></table>{{ $jobs->links() }}</div>@endif
@endsection
