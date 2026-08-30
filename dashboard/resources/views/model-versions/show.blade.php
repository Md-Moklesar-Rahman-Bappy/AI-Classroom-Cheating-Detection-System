@extends("layouts.bootstrap")
@section("title","Model Detail")
@section("content")
<h2>{{ $modelVersion->name }} {{ $modelVersion->version }}</h2>
<div class="card p-3"><p>Checksum: <code>{{ $modelVersion->checksum_sha256 }}</code></p><p>License: {{ $modelVersion->license }}</p><p>Class list: {{ json_encode($modelVersion->class_list) }}</p></div>
@endsection
