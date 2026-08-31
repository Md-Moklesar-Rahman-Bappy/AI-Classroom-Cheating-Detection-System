@extends("layouts.bootstrap")
@section("title","Settings")
@section("content")
<div class="mb-4"><h1 class="h4 mb-1" style="font-weight:700;letter-spacing:-.02em">Settings</h1><p class="text-muted mb-0" style="font-size:13px">Grouped by category — secrets are masked, never fully displayed</p></div>

<div class="row g-4">
<div class="col-12 col-lg-6">
<div class="card h-100">
<div class="card-header bg-white"><h2 class="h6 mb-0" style="font-size:11px;letter-spacing:.06em;text-transform:uppercase"><i class="bi bi-cpu me-2 text-primary" aria-hidden="true"></i>AI Service</h2></div>
<div class="card-body" style="font-size:13px">
<div class="d-flex justify-content-between align-items-center py-2 border-bottom"><span class="text-muted">Service URL</span><code class="text-mono" style="font-size:11px">{{ config('services.ai.url') ?? env('AI_SERVICE_URL','—') }}</code></div>
<div class="d-flex justify-content-between align-items-center py-2 border-bottom"><span class="text-muted">Token</span><span class="d-flex align-items-center gap-2"><code class="text-mono" style="font-size:11px">••••••••{{ substr((string)(config('services.ai.token') ?? env('AI_SERVICE_TOKEN','')),-4) ?: '****' }}</code><span class="badge bg-success status-badge"><i class="bi bi-shield-lock me-1" aria-hidden="true"></i> Masked</span></span></div>
<div class="d-flex justify-content-between align-items-center py-2"><span class="text-muted">Timeout</span><span style="font-variant-numeric:tabular-nums">{{ config('services.ai.timeout') ?? '—' }}s</span></div>
<div class="alert alert-info py-2 mt-3 mb-0" style="font-size:12px"><i class="bi bi-info-circle me-1" aria-hidden="true"></i> Configure via <code class="text-mono">.env</code> — <code class="text-mono">AI_SERVICE_TOKEN</code>, <code class="text-mono">AI_SERVICE_URL</code>.</div>
</div>
</div>
</div>
<div class="col-12 col-lg-6">
<div class="card h-100">
<div class="card-header bg-white"><h2 class="h6 mb-0" style="font-size:11px;letter-spacing:.06em;text-transform:uppercase"><i class="bi bi-shield-lock me-2 text-success" aria-hidden="true"></i>Security</h2></div>
<div class="card-body" style="font-size:13px">
<div class="d-flex justify-content-between align-items-center py-2 border-bottom"><span class="text-muted">Camera encryption</span><span class="badge bg-dark status-badge">APP_KEY • AES-256-GCM</span></div>
<div class="d-flex justify-content-between align-items-center py-2 border-bottom"><span class="text-muted">APP_KEY</span><span class="d-flex align-items-center gap-2"><code class="text-mono" style="font-size:11px">••••••••{{ substr((string)config('app.key'),-4) }}</code><span class="badge bg-success status-badge">Masked</span></span></div>
<div class="d-flex justify-content-between align-items-center py-2"><span class="text-muted">Evidence storage</span><span class="badge bg-light text-dark border status-badge"><i class="bi bi-folder me-1" aria-hidden="true"></i> Outside public/</span></div>
<div class="alert alert-warning py-2 mt-3 mb-0" style="font-size:12px"><i class="bi bi-exclamation-triangle me-1" aria-hidden="true"></i> Secrets never shown in full — only last 4 chars.</div>
</div>
</div>
</div>
<div class="col-12 col-lg-6">
<div class="card h-100">
<div class="card-header bg-white"><h2 class="h6 mb-0" style="font-size:11px;letter-spacing:.06em;text-transform:uppercase"><i class="bi bi-gear me-2 text-muted" aria-hidden="true"></i>Application</h2></div>
<div class="card-body" style="font-size:13px">
<div class="d-flex justify-content-between py-2 border-bottom"><span class="text-muted">Environment</span><span class="badge @if(config('app.env')=='production') bg-danger @else bg-warning text-dark @endif status-badge">{{ config('app.env') }}</span></div>
<div class="d-flex justify-content-between py-2 border-bottom"><span class="text-muted">Debug</span><span class="badge @if(config('app.debug')) bg-warning text-dark @else bg-success @endif status-badge">{{ config('app.debug') ? 'Enabled' : 'Disabled' }}</span></div>
<div class="d-flex justify-content-between py-2"><span class="text-muted">Version</span><span class="badge bg-light text-dark border status-badge">v{{ config('app.version','1.1') }}</span></div>
</div>
</div>
</div>
<div class="col-12 col-lg-6">
<div class="card h-100">
<div class="card-header bg-white"><h2 class="h6 mb-0" style="font-size:11px;letter-spacing:.06em;text-transform:uppercase"><i class="bi bi-question-circle me-2 text-info" aria-hidden="true"></i>Help</h2></div>
<div class="card-body" style="font-size:13px">
<p class="text-muted" style="font-size:13px">Project notice, compatibility report and registration info.</p>
<div class="d-flex flex-wrap gap-2">
<a href="{{ route("help.index") }}" class="btn btn-sm btn-outline-primary focus-ring"><i class="bi bi-question-circle me-1" aria-hidden="true"></i> Help Center</a>
<a href="{{ route("audit-logs.index") }}" class="btn btn-sm btn-outline-secondary focus-ring">Audit Logs</a>
</div>
</div>
</div>
</div>
</div>
@endsection
