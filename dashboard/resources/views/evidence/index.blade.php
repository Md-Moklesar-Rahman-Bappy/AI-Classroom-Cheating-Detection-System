@extends("layouts.bootstrap")
@section("title","Evidence")
@section("content")
<h2>Evidence for Event {{ $detectionEvent->id }}</h2>
@if($evidences->isEmpty())<div class="alert alert-info">No evidence yet. Evidence is incident-only and stored outside public directory.</div>
@else<ul class="list-group">@foreach($evidences as $ev)<li class="list-group-item"><a href="{{ route("evidence.show",$ev) }}">View Evidence {{ $ev->id }}</a> - {{ $ev->file_type }} - frame {{ $ev->frame_number }}</li>@endforeach</ul>@endif
@endsection
