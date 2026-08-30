@extends("layouts.bootstrap")
@section("title","Edit Video Asset")
@section("content")
<div class="mb-3"><h2>Edit Video Asset</h2><a href="{{ route('video-assets.index') }}" class="btn btn-outline-secondary btn-sm">Back</a></div>
<form method="POST" action="{{ route('video-assets.update', $videoAsset) }}">
    @csrf
    @method('PUT')
    <div class="mb-3">
        <label for="exam_session_id" class="form-label">Exam Session</label>
        <select name="exam_session_id" id="exam_session_id" class="form-select" required>
            @foreach($sessions as $s)
            <option value="{{ $s->id }}" {{ $videoAsset->exam_session_id == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="mb-3">
        <label for="original_filename" class="form-label">Original Filename</label>
        <input type="text" class="form-control" id="original_filename" name="original_filename" value="{{ old('original_filename', $videoAsset->original_filename) }}">
    </div>
    <div class="mb-3">
        <label for="validation_status" class="form-label">Status</label>
        <select name="validation_status" id="validation_status" class="form-select">
            <option value="pending" {{ $videoAsset->validation_status == 'pending' ? 'selected' : '' }}>Pending</option>
            <option value="valid" {{ $videoAsset->validation_status == 'valid' ? 'selected' : '' }}>Valid</option>
            <option value="invalid" {{ $videoAsset->validation_status == 'invalid' ? 'selected' : '' }}>Invalid</option>
        </select>
    </div>
    <button type="submit" class="btn btn-primary">Save</button>
</form>
@endsection
