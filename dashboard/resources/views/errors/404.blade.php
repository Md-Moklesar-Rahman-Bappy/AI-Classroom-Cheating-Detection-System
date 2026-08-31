@extends("layouts.bootstrap")
@section("title","Error 404 — Not Found")
@section("content")
<div class="d-flex justify-content-center align-items-center py-4 py-md-5">
    <div class="card shadow-sm" style="max-width:560px;width:100%;border-radius:16px">
        <div class="card-body p-4 p-md-5 text-center">
            <div class="mx-auto d-flex align-items-center justify-content-center rounded-3 mb-3" style="width:48px;height:48px;background:#F1F5F9;color:#64748B;border:1px solid #E2E8F0" aria-hidden="true">
                <i class="bi bi-file-earmark-x" style="font-size:24px"></i>
            </div>
            <div style="font-size:56px;font-weight:700;line-height:1;letter-spacing:-.04em;color:var(--color-text)">404</div>
            <h1 class="h5 fw-semibold mt-2 mb-1">Not found — the page or resource doesn't exist</h1>
            <p class="mb-0" style="font-size:13px;color:var(--color-text-muted);line-height:1.6">The page you requested couldn't be found. It may have been moved, deleted, or you may have mistyped the URL. Check the address and try again.</p>
            <div class="d-flex flex-wrap justify-content-center gap-2 mt-4">
                <button type="button" onclick="history.length>1?history.back():window.location.href='{{ route('dashboard') }}'" class="btn btn-outline-secondary focus-ring" style="font-size:13px"><i class="bi bi-arrow-left me-1" aria-hidden="true"></i> Go Back</button>
                <a href="{{ route('dashboard') }}" class="btn btn-primary focus-ring" style="font-size:13px"><i class="bi bi-speedometer2 me-1" aria-hidden="true"></i> Dashboard</a>
                <a href="{{ url('/') }}" class="btn btn-outline-primary focus-ring" style="font-size:13px"><i class="bi bi-house me-1" aria-hidden="true"></i> Home</a>
            </div>
            @php $cid = request()->header('X-Correlation-Id') ?? request()->header('X-Request-ID') ?? ''; @endphp
            @if($cid)
            <div class="mt-4 pt-3 border-top text-start">
                <div class="d-flex align-items-center gap-2" style="font-size:11px;color:var(--color-text-subtle)"><i class="bi bi-fingerprint" aria-hidden="true"></i> Correlation ID</div>
                <code class="d-block mt-1 p-2 rounded text-mono bg-light border text-break" style="font-size:11px;color:var(--color-text-muted)">{{ $cid }}</code>
            </div>
            @endif
            <div class="mt-3" style="font-size:12px;color:var(--color-text-subtle)">Error code: 404 &middot; If this keeps happening, verify the link or contact support.</div>
        </div>
    </div>
</div>
@endsection
