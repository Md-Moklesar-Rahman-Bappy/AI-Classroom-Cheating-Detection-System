@extends("layouts.bootstrap")
@section("title","Camera Detail")
@section("content")
<h2>{{ $cameraSource->name }}</h2>
<div class="card p-3"><p>Type: {{ $cameraSource->source_type }} <span class="badge bg-info">{{ $cameraSource->source_type }}</span></p><p>Identifier: {{ $cameraSource->identifier }}</p><p>Status: <span class="badge bg-secondary">{{ $cameraSource->status }}</span></p><p>Has Credentials: @if($cameraSource->has_credentials)<span class="badge bg-success">Yes (encrypted)</span>@else<span class="badge bg-secondary">No</span>@endif</p><p class="text-muted small">Decrypted credentials never displayed.</p></div>
@endsection
