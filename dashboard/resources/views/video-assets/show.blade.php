@extends("layouts.bootstrap")
@section("title","Video Detail")
@section("content")
<h2>{{ $videoAsset->original_filename }}</h2>
<div class="card p-3"><p>Stored: <code>{{ $videoAsset->stored_filename }}</code> <span class="badge bg-secondary">outside public path</span></p><p>Mime: {{ $videoAsset->mime_type }}</p><p>Size: {{ number_format($videoAsset->size_bytes/1024,1) }} KB</p><p>Status: <span class="badge bg-success">{{ $videoAsset->validation_status }}</span></p></div>
@endsection
