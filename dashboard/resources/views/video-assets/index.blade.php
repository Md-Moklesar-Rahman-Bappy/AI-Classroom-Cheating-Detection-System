@extends("layouts.bootstrap")
@section("title","Video Assets")
@section("content")
<div class="d-flex justify-content-between mb-3"><h2>Video Assets</h2><a href="{{ route("video-assets.create") }}" class="btn btn-primary">Upload Video</a></div>
@if($assets->isEmpty())<div class="card p-4 text-center text-muted">No videos. Upload a valid mp4/avi/mov/mkv.</div>
@else<div class="table-responsive"><table class="table"><thead><tr><th>Original</th><th>Stored</th><th>Mime</th><th>Status</th><th>Actions</th></tr></thead><tbody>@foreach($assets as $a)<tr><td>{{ $a->original_filename }}</td><td class="text-muted small">{{ Str::limit($a->stored_filename,20) }}</td><td>{{ $a->mime_type }}</td><td><span class="badge @if($a->validation_status=="valid") bg-success @elseif($a->validation_status=="invalid") bg-danger @else bg-warning text-dark @endif">{{ $a->validation_status }}</span></td><td><a href="{{ route("video-assets.show",$a) }}" class="btn btn-sm btn-outline-primary">View</a></td></tr>@endforeach</tbody></table>{{ $assets->links() }}</div>@endif
@endsection
