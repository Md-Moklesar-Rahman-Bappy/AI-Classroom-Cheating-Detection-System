<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>Sign in — AI Classroom</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400&display=swap" rel="stylesheet">
<style>
:root{--primary:#2563EB;--primary-dark:#1D4ED8;--sidebar:#0F172A;--border:#E2E8F0;--text:#172033;--muted:#64748B;--bg:#F8FAFC;--surface:#FFFFFF;--success:#22C55E;--danger:#EF4444;--radius-md:12px;--radius-lg:16px;--shadow-md:0 4px 12px rgba(15,23,42,.08)}
*{font-family:Inter,system-ui,sans-serif}body{background:var(--bg);min-height:100vh;display:flex;align-items:center;justify-content:center;padding:16px;color:var(--text)}
.login-wrap{max-width:980px;width:100%;display:grid;grid-template-columns:1fr 1fr;border:1px solid var(--border);border-radius:var(--radius-lg);overflow:hidden;background:var(--surface);box-shadow:var(--shadow-md)}
.brand-panel{background:var(--sidebar);color:#CBD5E1;padding:28px;display:flex;flex-direction:column}
.form-panel{padding:28px}
.form-label{font-size:11px;letter-spacing:.06em;text-transform:uppercase;color:var(--muted);font-weight:600;margin-bottom:4px}
.form-control:focus{box-shadow:0 0 0 3px rgba(37,99,235,.15);border-color:var(--primary)}
.illustration{border:1px solid rgba(255,255,255,.1);border-radius:12px;overflow:hidden;background:#0B1224;margin-top:16px}
.ill-row{display:flex;gap:8px;flex-wrap:wrap;margin-top:12px}
.ill-row span{font-size:11px;background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.08);border-radius:999px;padding:4px 8px;display:inline-flex;align-items:center;gap:4px}
.btn-primary{background:var(--primary);border-color:var(--primary);font-weight:600}.btn-primary:hover{background:var(--primary-dark);border-color:var(--primary-dark)}
.invalid-feedback{font-size:12px}
@media(max-width:767.98px){.login-wrap{grid-template-columns:1fr}.brand-panel{padding:20px}.form-panel{padding:20px}}
</style>
</head>
<body>
<main class="login-wrap" aria-labelledby="login-heading">
<div class="brand-panel">
<div class="d-flex align-items-center gap-2 mb-3">
<span class="bg-primary rounded d-flex align-items-center justify-content-center" style="width:36px;height:36px"><i class="bi bi-shield-lock text-white" aria-hidden="true"></i></span>
<div><div style="color:#fff;font-weight:700;letter-spacing:-.02em">AI Classroom</div><div style="font-size:10px;letter-spacing:.08em;text-transform:uppercase;color:#94A3B8">Surveillance Platform</div></div>
</div>
<h1 id="login-heading" class="h4 mb-2" style="color:#fff;font-weight:700;letter-spacing:-.02em">Cheating Detection System</h1>
<p style="font-size:13px;line-height:1.6;color:#94A3B8">Authorized access only. Detection alerts are observable events requiring human review — not proof of misconduct. All access is audited.</p>
<div class="illustration" aria-hidden="true">
<div style="height:28px;background:#172033;display:flex;align-items:center;gap:6px;padding:0 10px;font-size:11px;color:#94A3B8"><span style="width:8px;height:8px;border-radius:50%;background:#EF4444"></span><span style="width:8px;height:8px;border-radius:50%;background:#EAB308"></span><span style="width:8px;height:8px;border-radius:50%;background:#22C55E"></span><i class="bi bi-camera-video ms-2"></i> AI surveillance · YOLO11n · ByteTrack</div>
<div style="height:130px;background:linear-gradient(135deg,#1e293b,#0f172a);position:relative;display:flex;align-items:center;justify-content:center">
<div style="position:absolute;left:14%;top:22%;width:22%;height:56%;border:2px solid #22C55E;border-radius:6px"><span class="badge bg-success" style="font-size:8px;position:absolute;top:-10px;left:0">person 0.92</span></div>
<div style="position:absolute;left:44%;top:18%;width:20%;height:52%;border:2px solid #22C55E;border-radius:6px"><span class="badge bg-success" style="font-size:8px;position:absolute;top:-10px;left:0">person 0.88</span></div>
<div style="position:absolute;right:18%;top:26%;width:18%;height:44%;border:2px solid #F59E0B;border-radius:6px"><span class="badge bg-warning text-dark" style="font-size:7px;position:absolute;top:-10px;left:0">B1 review</span></div>
<div style="position:absolute;left:50%;transform:translateX(-50%);bottom:10px;background:rgba(15,23,42,.9);border:1px solid rgba(255,255,255,.1);border-radius:999px;padding:3px 8px;font-size:10px;color:#E2E8F0"><i class="bi bi-activity text-warning me-1"></i> 24 FPS · Evidence preserved</div>
</div>
</div>
<div class="ill-row">
<span><i class="bi bi-person-bounding-box text-success"></i> Person</span>
<span><i class="bi bi-phone text-danger"></i> Phone D2</span>
<span><i class="bi bi-eye text-warning"></i> Orientation B1-B4</span>
<span><i class="bi bi-journal-text text-primary"></i> Evidence</span>
</div>
<ul class="list-unstyled mt-3 mb-0" style="font-size:12px;color:#94A3B8">
<li class="d-flex gap-2 mb-1"><i class="bi bi-shield-check text-success" aria-hidden="true"></i> Encrypted credentials • Private storage • Audit trail</li>
<li class="d-flex gap-2 mb-1"><i class="bi bi-people text-primary" aria-hidden="true"></i> Roles: System Admin, Exam Admin, Reviewer, Invigilator, Auditor</li>
</ul>
<div class="mt-auto pt-3" style="font-size:11px;color:#64748B;border-top:1px solid #1e293b;margin-top:16px;padding-top:12px">
<div class="d-flex align-items-center gap-2 mb-2"><i class="bi bi-exclamation-triangle text-warning" aria-hidden="true"></i><strong style="color:#CBD5E1">Responsible use:</strong> Do not use alerts as sole basis for disciplinary action.</div>
<span>Need access? Contact your administrator. No public registration.</span>
</div>
</div>
<div class="form-panel">
<div class="d-flex justify-content-between align-items-center mb-3">
<h2 class="h5 mb-0" style="font-weight:700">Sign in</h2>
<a href="/" class="text-decoration-none" style="font-size:12px"><i class="bi bi-arrow-left me-1"></i> Home</a>
</div>
<p class="text-muted mb-3" style="font-size:13px">Enter your credentials to continue to the dashboard.</p>
<x-auth-session-status class="mb-3" :status="session('status')" />
@if($errors->any())
<div class="alert alert-danger py-2" role="alert" style="font-size:12px"><i class="bi bi-exclamation-circle me-1"></i> Please correct the highlighted fields.</div>
@endif
<form method="POST" action="{{ route('login') }}" novalidate>
@csrf
<div class="mb-3">
<label for="email" class="form-label">Email <span class="text-danger" aria-hidden="true">*</span></label>
<input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username" aria-required="true" aria-describedby="emailHelp" class="form-control @error('email') is-invalid @enderror" placeholder="name@institution.edu">
<div id="emailHelp" class="form-text" style="font-size:11px">Institutional email only.</div>
@error('email')<div class="invalid-feedback d-block" role="alert"><i class="bi bi-exclamation-circle me-1"></i>{{ $message }}</div>@enderror
</div>
<div class="mb-3">
<label for="password" class="form-label">Password <span class="text-danger" aria-hidden="true">*</span></label>
<div class="input-group">
<input id="password" type="password" name="password" required autocomplete="current-password" aria-required="true" class="form-control @error('password') is-invalid @enderror" placeholder="••••••••">
<button class="btn btn-outline-secondary" type="button" onclick="togglePwd('password',this)" aria-label="Toggle password visibility"><i class="bi bi-eye" aria-hidden="true"></i></button>
</div>
@error('password')<div class="invalid-feedback d-block" role="alert"><i class="bi bi-exclamation-circle me-1"></i>{{ $message }}</div>@enderror
</div>
<div class="d-flex justify-content-between align-items-center mb-3">
<label for="remember_me" class="d-flex align-items-center gap-2 m-0" style="font-size:13px;cursor:pointer">
<input id="remember_me" type="checkbox" name="remember" class="form-check-input m-0"> Remember me
</label>
@if(Route::has('password.request'))
<a href="{{ route('password.request') }}" class="text-decoration-none" style="font-size:13px">Forgot password?</a>
@endif
</div>
<button type="submit" class="btn btn-primary w-100" style="font-weight:600"><i class="bi bi-box-arrow-in-right me-1" aria-hidden="true"></i> Sign in</button>
<div class="text-center mt-3" style="font-size:13px"><span class="text-muted">Don't have an account?</span> <a href="{{ route('register') }}" class="text-decoration-none fw-semibold">Create account</a></div>
<p class="text-center text-muted mt-2 mb-0" style="font-size:11px"><i class="bi bi-lock me-1" aria-hidden="true"></i> No public registration. Accounts are provisioned by administrators.</p>
</form>
</div>
</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function togglePwd(id,btn){const i=document.getElementById(id);const icon=btn.querySelector('i');const isPwd=i.type==='password';i.type=isPwd?'text':'password';icon.className=isPwd?'bi bi-eye-slash':'bi bi-eye';btn.setAttribute('aria-label',isPwd?'Hide password':'Show password')}
</script>
</body>
</html>
