@extends("layouts.bootstrap")
@section("title","Playback — Overlay")
@section("content")
<div class="mb-4">
    <div class="d-flex justify-content-between align-items-center mb-2">
        <h2 class="h5 mb-0" style="font-weight:700;">Synchronized Playback Overlay</h2>
        <a href="{{ route('video-assets.index') }}" class="btn btn-sm btn-outline-secondary">Back to Assets</a>
    </div>
    <p class="text-muted mb-0" style="font-size:13px;">Video: {{ $videoAsset->original_filename }} · Fixture overlay loaded @if($hasOverlay) <span class="badge bg-success">Active</span> @else <span class="badge bg-secondary">Unavailable</span> @endif</p>
</div>

<div class="card p-3">
    <x-playback-player :videoUrl="route('playback.stream', $videoAsset)" :tracksUrl="$hasOverlay ? route('playback.tracks', $videoAsset) : null" :events="$events" />
</div>

<div class="card mt-3">
    <div class="card-header bg-white"><h5 class="h6 mb-0" style="font-size:13px;letter-spacing:.06em;text-transform:uppercase;">Event Timeline</h5></div>
    <div class="card-body">
        <table class="table table-sm mb-0" style="font-size:13px;">
            <thead><tr><th>Event</th><th>Category</th><th>Time (s)</th><th>Review</th><th>Track</th></tr></thead>
            <tbody>
            @forelse($events as $e)
                <tr>
                    <td>{{ $e->event_type }}</td>
                    <td>{{ $e->event_category ?? '—' }}</td>
                    <td>{{ number_format($e->started_at_seconds ?? 0, 2) }}</td>
                    <td><span class="badge @if($e->review_status=='pending') bg-warning text-dark @elseif($e->review_status=='confirmed_suspicious') bg-danger @else bg-success @endif">{{ $e->review_status }}</span></td>
                    <td><span class="badge bg-dark">{{ $e->temporary_track_id }}</span></td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-muted">No events linked to this asset.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
