@extends("layouts.bootstrap")
@section("title","Evidence Detail")
@section("content")
@php
$isS3B4 = in_array($evidence->event_type ?? $evidence->event->event_type ?? '', ['S3','B4']);
$event = $evidence->event;
$pair = $event ? $event->evidences()->orderBy('frame_number')->get() : collect([$evidence]);
$trigger = $pair->firstWhere('render_mode','trigger') ?? $evidence;
$lastDet = $pair->firstWhere('render_mode','last_detected');
$debug = $evidence->debug_json ?? [];
$twoFrame = $debug['two_frame_evidence'] ?? null;
@endphp
<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
<div><h1 class="h5 mb-1" style="font-weight:700">Evidence Detail</h1><p class="text-muted mb-0" style="font-size:13px">Two-frame temporal evidence — protected, authorized access only</p></div>
<a href="{{ route('evidence.index') }}" class="btn btn-sm btn-outline-secondary">Back to Gallery</a>
</div>

@php $integrity = $evidence->integrity_status ?? 'not_verified'; $completeness = $evidence->completeness_status ?? 'complete'; @endphp
<div class="d-flex gap-2 mb-2"><span class="badge @if($integrity=='verified') bg-success @elseif($integrity=='failed') bg-danger @else bg-secondary @endif">Integrity: {{ ucfirst(str_replace('_',' ',$integrity)) }}</span><span class="badge bg-info">Evidence pair: @if($isS3B4) trigger + last_detected @else single @endif</span><span class="badge bg-light text-dark border" title="Hash proves stored bytes match digest, not that detection is correct">SHA256 verified</span></div>
@if($isS3B4)
<div class="alert alert-warning py-2" style="font-size:12px"><i class="bi bi-shield-exclamation me-1"></i><strong>Responsible AI:</strong> B4 Possible Seat Departure / S3 Tracking Lost are <em>Observable events requiring human review</em> — not Cheater / Guilty / Fraud.</div>
<div class="row g-3 mb-4">
<div class="col-12 col-lg-6">
<div class="card h-100">
<div class="card-header bg-white d-flex justify-content-between align-items-center"><span class="badge bg-primary">Left: Last Detected Frame</span><span class="text-muted" style="font-size:11px">Frame {{ $lastDet->frame_number ?? $twoFrame['last_detection_frame_number'] ?? '—' }} · t={{ number_format($lastDet->captured_at_seconds ?? $twoFrame['last_detection_timestamp'] ?? 0,1) }}s</span></div>
<div class="bg-light d-flex align-items-center justify-content-center" style="height:280px;overflow:hidden">
@if($lastDet && Storage::disk('local')->exists($lastDet->file_path))
<img src="{{ route('evidence.show',$lastDet) }}" alt="Last detected frame {{ $lastDet->frame_number }}" style="width:100%;height:280px;object-fit:contain;cursor:zoom-in" onclick="window.open(this.src,'_blank')" loading="lazy">
@else
<div class="text-center p-4"><i class="bi bi-image text-muted" style="font-size:32px"></i><p class="text-muted mt-2" style="font-size:12px">Last known position unavailable</p><p class="text-muted" style="font-size:11px">No valid last-detected frame — event created without position image</p></div>
@endif
</div>
<div class="card-body py-2" style="font-size:12px">
<div><strong>Track #{{ $event->temporary_track_id ?? '—' }}</strong> · Last Known Position</div>
<div class="text-muted">Full-person bbox from that exact frame · {{ $lastDet->width ?? 640 }}×{{ $lastDet->height ?? 360 }} · bbox_format xyxy</div>
<div class="mt-1 d-flex gap-2"><a href="{{ $lastDet ? route('evidence.download',$lastDet).'?format=jpg' : '#' }}" class="btn btn-sm btn-outline-primary {{ !$lastDet ? 'disabled' : '' }}" style="font-size:11px">Download JPG</a><a href="{{ $lastDet ? route('evidence.download',$lastDet).'?format=json' : '#' }}" class="btn btn-sm btn-outline-secondary {{ !$lastDet ? 'disabled' : '' }}" style="font-size:11px">JSON</a></div>
</div>
</div>
</div>
<div class="col-12 col-lg-6">
<div class="card h-100">
<div class="card-header bg-white d-flex justify-content-between align-items-center"><span class="badge bg-danger">Right: Trigger Frame</span><span class="text-muted" style="font-size:11px">Frame {{ $trigger->frame_number ?? '—' }} · t={{ number_format($trigger->captured_at_seconds ?? 0,1) }}s</span></div>
<div class="bg-light d-flex align-items-center justify-content-center" style="height:280px;overflow:hidden">
@if(Storage::disk('local')->exists($trigger->file_path))
<img src="{{ route('evidence.show',$trigger) }}" alt="Trigger frame {{ $trigger->frame_number }}" style="width:100%;height:280px;object-fit:contain;cursor:zoom-in" onclick="window.open(this.src,'_blank')" loading="lazy">
@else
<div class="text-center p-4"><i class="bi bi-image text-muted" style="font-size:32px"></i><p class="text-muted mt-2" style="font-size:12px">Trigger frame unavailable</p></div>
@endif
</div>
<div class="card-body py-2" style="font-size:12px">
<div><strong>{{ $event->event_type ?? $trigger->event_type }} — {{ $event->event_type=='B4'?'Possible Seat Departure':'Tracking Lost' }}</strong></div>
<div class="text-muted">No stale person bbox drawn as current detection · Absent for {{ $twoFrame['absence_processed_frames'] ?? '—' }} processed observations</div>
<div class="mt-1 d-flex gap-2"><a href="{{ route('evidence.download',$trigger).'?format=jpg' }}" class="btn btn-sm btn-outline-primary" style="font-size:11px">Download JPG</a><a href="{{ route('evidence.download',$trigger).'?format=json' }}" class="btn btn-sm btn-outline-secondary" style="font-size:11px">JSON</a></div>
</div>
</div>
</div>
</div>
<div class="card mb-4">
<div class="card-body" style="font-size:13px">
<div class="row g-2">
<div class="col-6 col-md-3"><div class="text-muted" style="font-size:11px;letter-spacing:.06em;text-transform:uppercase">Event</div><span class="badge bg-dark">{{ $event->event_type ?? '—' }}</span></div>
<div class="col-6 col-md-3"><div class="text-muted" style="font-size:11px;letter-spacing:.06em;text-transform:uppercase">Track</div><span class="badge bg-secondary">#{{ $event->temporary_track_id ?? '—' }}</span></div>
<div class="col-6 col-md-3"><div class="text-muted" style="font-size:11px;letter-spacing:.06em;text-transform:uppercase">Last Detected</div><span style="font-variant-numeric:tabular-nums">Frame {{ $twoFrame['last_detection_frame_number'] ?? $lastDet->frame_number ?? '—' }} · {{ number_format($twoFrame['last_detection_timestamp'] ?? $lastDet->captured_at_seconds ?? 0,1) }}s</span></div>
<div class="col-6 col-md-3"><div class="text-muted" style="font-size:11px;letter-spacing:.06em;text-transform:uppercase">Trigger</div><span style="font-variant-numeric:tabular-nums">Frame {{ $twoFrame['trigger_frame_number'] ?? $trigger->frame_number ?? '—' }} · {{ number_format($twoFrame['trigger_timestamp'] ?? $trigger->captured_at_seconds ?? 0,1) }}s</span></div>
<div class="col-6 col-md-3"><div class="text-muted" style="font-size:11px;letter-spacing:.06em;text-transform:uppercase">Absence</div><span>{{ $twoFrame['absence_processed_frames'] ?? '—' }} frames</span></div>
<div class="col-6 col-md-3"><div class="text-muted" style="font-size:11px;letter-spacing:.06em;text-transform:uppercase">Threshold</div><span>{{ ($event->event_type??'')==='B4' ? 'B4 ≥45' : 'S3 ≥10 & <45' }}</span></div>
<div class="col-6 col-md-3"><div class="text-muted" style="font-size:11px;letter-spacing:.06em;text-transform:uppercase">Reason</div><span style="font-size:12px">Track absent — human review required</span></div>
<div class="col-6 col-md-3"><div class="text-muted" style="font-size:11px;letter-spacing:.06em;text-transform:uppercase">Rule</div><span style="font-size:11px">{{ $event->event_type=='B4'?'LeavingSeatRule':'TrackingLostRule' }}</span></div>
</div>
<div class="alert alert-info mt-3 mb-0 py-2" style="font-size:11px">Historical bbox is rendered only on its matching last-detected frame. Trigger frame shows current scene with no stale box. Processed size 640×360 (source 64×48, scale 10× / 7.5×, xyxy).</div>
</div>
</div>
@else
<div class="card mb-4">
<div class="card-header bg-white d-flex justify-content-between align-items-center"><span class="badge bg-warning text-dark">{{ $event->event_type ?? $evidence->event_type }} {{ $event->event_type=='B3'?'Looking Backward':'' }}</span><span class="text-muted" style="font-size:11px">Track #{{ $event->temporary_track_id ?? '—' }} · Frame {{ $evidence->frame_number }} · t={{ number_format($evidence->captured_at_seconds ?? 0,1) }}s</span></div>
<div class="bg-light d-flex align-items-center justify-content-center" style="height:360px;overflow:hidden">
@if(Storage::disk('local')->exists($evidence->file_path))
<img src="{{ route('evidence.show',$evidence) }}" alt="Evidence frame {{ $evidence->frame_number }}" style="width:100%;height:360px;object-fit:contain;cursor:zoom-in" onclick="window.open(this.src,'_blank')" loading="lazy">
@endif
</div>
<div class="card-body" style="font-size:12px"><div>Full-person bbox from same frame · Observable event requiring human review</div><div class="text-muted">B3 uses same-frame detection — not historical or nearby-person bbox.</div></div>
</div>
@endif
<div class="card mb-4">
<div class="card-body d-flex gap-2 flex-wrap">
<a href="{{ route('evidence.download',$evidence).'?format=jpg' }}" class="btn btn-sm btn-primary">Download JPG</a>
<a href="{{ route('evidence.download',$evidence).'?format=png' }}" class="btn btn-sm btn-outline-secondary">Download PNG</a>
<a href="{{ route('evidence.download',$evidence).'?format=json' }}" class="btn btn-sm btn-outline-dark">Download JSON</a>
@if($event)<a href="{{ route('detection-events.show',$event) }}" class="btn btn-sm btn-outline-primary">View Event</a>@endif
</div>
</div>
@endsection
