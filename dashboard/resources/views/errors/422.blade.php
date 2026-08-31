@extends("layouts.bootstrap")
@section("title","Error 422 — Unprocessable Content")
@section("content")
<div class="d-flex justify-content-center align-items-center py-4 py-md-5">
    <div class="card shadow-sm" style="max-width:560px;width:100%;border-radius:16px">
        <div class="card-body p-4 p-md-5 text-center">
            <div class="mx-auto d-flex align-items-center justify-content-center rounded-3 mb-3" style="width:48px;height:48px;background:var(--color-warning-soft);color:var(--color-warning)" aria-hidden="true">
                <i class="bi bi-exclamation-circle" style="font-size:24px"></i>
            </div>
            <div style="font-size:56px;font-weight:700;line-height:1;letter-spacing:-.04em;color:var(--color-text)">422</div>
            <h1 class="h5 fw-semibold mt-2 mb-1">Unprocessable — validation failed</h1>
            <p class="mb-3" style="font-size:13px;color:var(--color-text-muted);line-height:1.6">We couldn't process your request. Some fields may be missing or invalid. Check the form and correct any highlighted errors, then try again.</p>
            @if($errors->any())
            <div class="text-start p-3 rounded mb-3" style="background:var(--color-danger-soft);border:1px solid #fecaca">
                <div class="d-flex align-items-center gap-2 mb-2" style="font-size:12px;font-weight:600;color:var(--color-danger)"><i class="bi bi-exclamation-triangle" aria-hidden="true"></i> Please correct the following:</div>
                <ul class="mb-0 ps-3" style="font-size:13px;color:#7f1d1d;line-height:1.6">
                    @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            @else
            <div class="text-start p-3 rounded mb-3" style="background:var(--color-surface-muted);border:1px solid var(--color-border);font-size:13px;color:var(--color-text-muted)">
                <i class="bi bi-lightbulb me-1" aria-hidden="true"></i> Common causes: required field empty, invalid email format, file too large, or duplicate entry.
            </div>
            @endif
            @if(isset($exception) && $exception->getMessage() && !str_contains($exception->getMessage(), 'stack'))
            <div class="text-start mb-3 p-2 rounded bg-light border text-break" style="font-size:12px;color:var(--color-text-muted)">{{ $exception->getMessage() }}</div>
            @endif
            <div class="d-flex flex-wrap justify-content-center gap-2">
                <button type="button" onclick="history.back()" class="btn btn-primary focus-ring" style="font-size:13px"><i class="bi bi-arrow-left me-1" aria-hidden="true"></i> Go Back &amp; Fix</button>
                <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary focus-ring" style="font-size:13px"><i class="bi bi-speedometer2 me-1" aria-hidden="true"></i> Dashboard</a>
            </div>
            @php $cid = request()->header('X-Correlation-Id') ?? request()->header('X-Request-ID') ?? ''; @endphp
            @if($cid)
            <div class="mt-4 pt-3 border-top text-start">
                <div class="d-flex align-items-center gap-2" style="font-size:11px;color:var(--color-text-subtle)"><i class="bi bi-fingerprint" aria-hidden="true"></i> Correlation ID</div>
                <code class="d-block mt-1 p-2 rounded text-mono bg-light border text-break" style="font-size:11px;color:var(--color-text-muted)">{{ $cid }}</code>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
