@extends("layouts.bootstrap")
@section("title","Audit Logs")
@section("content")
<h2>Audit Logs</h2>
<div class="table-responsive"><table class="table"><thead><tr><th>Action</th><th>Actor</th><th>Target</th><th>Result</th><th>Time</th></tr></thead><tbody>@forelse($logs as $l)<tr><td>{{ $l->action }}</td><td>{{ $l->actor_id ?? "system" }}</td><td>{{ $l->target_type }}:{{ $l->target_id }}</td><td><span class="badge @if($l->result=="success") bg-success @else bg-danger @endif">{{ $l->result }}</span></td><td>{{ $l->created_at }}</td></tr>@empty<tr><td colspan="5" class="text-center text-muted">No logs.</td></tr>@endforelse</tbody></table>{{ $logs->links() }}</div>
@endsection
