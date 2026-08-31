@extends("layouts.bootstrap")
@section("title","Error 401 — Unauthorized")
@section("content")
<div class="d-flex justify-content-center align-items-center py-4 py-md-5">
    <div class="card shadow-sm" style="max-width:560px;width:100%;border-radius:16px">
        <div class="card-body p-4 p-md-5 text-center">
            <div class="mx-auto d-flex align-items-center justify-content-center rounded-3 mb-3" style="width:48px;height:48px;background:var(--color-warning-soft);color:var(--color-warning)" aria-hidden="true">
                <i class="bi bi-box-arrow-in-right" style="font-size:24px"></i>
            </div>
            <div style="font-size:56px;font-weight:700;line-height:1;letter-spacing:-.04em;color:var(--color-text)">401</div>
            <h1 class="h5 fw-semibold mt-2 mb-1">Unauthorized — please sign in</h1>
            <p class="mb-0" style="font-size:13px;color:var(--color-text-muted);line-height:1.6">You need to sign in to access this page. Your session may have expired or you are not authenticated. Please sign in and try again.</p>
            <div class="d-flex flex-wrap justify-content-center gap-2 mt-4">
                <button type="button" onclick="history.length>1?history.back():window.location.href='{{ url('/') }}'" class="btn btn-outline-secondary focus-ring" style="font-size:13px"><i class="bi bi-arrow-left me-1" aria-hidden="true"></i> Go Back</button>
                <a href="{{ url('/login') }}" class="btn btn-primary focus-ring" style="font-size:13px"><i class="bi bi-box-arrow-in-right me-1" aria-hidden="true"></i> Sign In</a>
                <a href="{{ route('dashboard') }}" class="btn btn-outline-primary focus-ring" style="font-size:13px"><i class="bi bi-speedometer2 me-1" aria-hidden="true"></i> Dashboard</a>
            </div>
            @php $cid = request()->header('X-Correlation-Id') ?? request()->header('X-Request-ID') ?? ''; @endphp
            @if($cid)
            <div class="mt-4 pt-3 border-top text-start">
                <div class="d-flex align-items-center gap-2" style="font-size:11px;color:var(--color-text-subtle)"><i class="bi bi-fingerprint" aria-hidden="true"></i> Correlation ID</div>
                <code class="d-block mt-1 p-2 rounded text-mono bg-light border text-break" style="font-size:11px;color:var(--color-text-muted)">{{ $cid }}</code>
            </div>
            @endif
            <div class="mt-3" style="font-size:12px;color:var(--color-text-subtle)">If you were signed in, try refreshing or signing in again. Contact support if this persists.</div>
        </div>
    </div>
</div>
@endsection
