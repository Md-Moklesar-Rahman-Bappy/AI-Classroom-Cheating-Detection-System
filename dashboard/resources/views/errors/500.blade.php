@extends("layouts.bootstrap")
@section("title","Error 500 — Server Error")
@section("content")
<div class="d-flex justify-content-center align-items-center py-4 py-md-5">
    <div class="card shadow-sm" style="max-width:560px;width:100%;border-radius:16px">
        <div class="card-body p-4 p-md-5 text-center">
            <div class="mx-auto d-flex align-items-center justify-content-center rounded-3 mb-3" style="width:48px;height:48px;background:var(--color-danger-soft);color:var(--color-danger)" aria-hidden="true">
                <i class="bi bi-bug" style="font-size:24px"></i>
            </div>
            <div style="font-size:56px;font-weight:700;line-height:1;letter-spacing:-.04em;color:var(--color-text)">500</div>
            <h1 class="h5 fw-semibold mt-2 mb-1">Server Error — something went wrong</h1>
            <p class="mb-0" style="font-size:13px;color:var(--color-text-muted);line-height:1.6">An unexpected error occurred on our side. Our team has been notified. Please try again in a moment. If it persists, contact support with the details below.</p>
            <div class="d-flex flex-wrap justify-content-center gap-2 mt-4">
                <button type="button" onclick="window.location.reload()" class="btn btn-primary focus-ring" style="font-size:13px"><i class="bi bi-arrow-clockwise me-1" aria-hidden="true"></i> Try Again</button>
                <button type="button" onclick="history.length>1?history.back():window.location.href='{{ route('dashboard') }}'" class="btn btn-outline-secondary focus-ring" style="font-size:13px"><i class="bi bi-arrow-left me-1" aria-hidden="true"></i> Go Back</button>
                <a href="{{ route('dashboard') }}" class="btn btn-outline-primary focus-ring" style="font-size:13px"><i class="bi bi-speedometer2 me-1" aria-hidden="true"></i> Dashboard</a>
            </div>
            @php $cid = request()->header('X-Correlation-Id') ?? request()->header('X-Request-ID') ?? ''; @endphp
            <div class="mt-4 pt-3 border-top text-start">
                @if($cid)
                <div class="d-flex align-items-center gap-2" style="font-size:11px;color:var(--color-text-subtle)"><i class="bi bi-fingerprint" aria-hidden="true"></i> Correlation ID — include when contacting support</div>
                <code class="d-block mt-1 p-2 rounded text-mono bg-light border text-break" style="font-size:11px;color:var(--color-text-muted)">{{ $cid }}</code>
                @else
                <div class="d-flex align-items-center gap-2" style="font-size:11px;color:var(--color-text-subtle)"><i class="bi bi-clock-history" aria-hidden="true"></i> Timestamp</div>
                <code class="d-block mt-1 p-2 rounded text-mono bg-light border" style="font-size:11px;color:var(--color-text-muted)">{{ now()->toIso8601String() }}</code>
                <div style="font-size:11px;color:var(--color-text-subtle)" class="mt-1">No sensitive details are shown here for security.</div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
