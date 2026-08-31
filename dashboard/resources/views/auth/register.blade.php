<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>Create account — AI Classroom</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
:root{--primary:#2563EB;--primary-dark:#1D4ED8;--sidebar:#0F172A;--border:#E2E8F0;--text:#172033;--muted:#64748B;--bg:#F8FAFC;--surface:#FFFFFF;--success:#22C55E;--warning:#D97706;--danger:#EF4444;--radius-lg:16px;--shadow-md:0 4px 12px rgba(15,23,42,.08)}
*{font-family:Inter,system-ui,sans-serif}body{background:var(--bg);min-height:100vh;display:flex;align-items:center;justify-content:center;padding:16px;color:var(--text)}
.wrap{max-width:1000px;width:100%;display:grid;grid-template-columns:1fr 1fr;border:1px solid var(--border);border-radius:var(--radius-lg);overflow:hidden;background:var(--surface);box-shadow:var(--shadow-md)}
.brand-panel{background:var(--sidebar);color:#CBD5E1;padding:28px;display:flex;flex-direction:column}
.form-panel{padding:28px}
.form-label{font-size:11px;letter-spacing:.06em;text-transform:uppercase;color:var(--muted);font-weight:600;margin-bottom:4px}
.form-control:focus{box-shadow:0 0 0 3px rgba(37,99,235,.15);border-color:var(--primary)}
.strength{height:6px;background:#E2E8F0;border-radius:999px;overflow:hidden;margin-top:6px}
.strength-bar{height:100%;width:0%;transition:width .2s,background .2s;border-radius:999px}
.hint{font-size:11px;color:var(--muted);margin-top:4px}
.btn-primary{background:var(--primary);border-color:var(--primary);font-weight:600}.btn-primary:hover{background:var(--primary-dark);border-color:var(--primary-dark)}
.ill-row{display:flex;gap:8px;flex-wrap:wrap;margin-top:12px}
.ill-row span{font-size:11px;background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.08);border-radius:999px;padding:4px 8px;display:inline-flex;align-items:center;gap:4px}
@media(max-width:767.98px){.wrap{grid-template-columns:1fr}.brand-panel{padding:20px}.form-panel{padding:20px}}
</style>
</head>
<body>
<main class="wrap" aria-labelledby="register-heading">
<div class="brand-panel">
<div class="d-flex align-items-center gap-2 mb-3">
<span class="bg-primary rounded d-flex align-items-center justify-content-center" style="width:36px;height:36px"><i class="bi bi-shield-lock text-white"></i></span>
<div><div style="color:#fff;font-weight:700;letter-spacing:-.02em">AI Classroom</div><div style="font-size:10px;letter-spacing:.08em;text-transform:uppercase;color:#94A3B8">Surveillance Platform</div></div>
</div>
<h1 id="register-heading" class="h4 mb-2" style="color:#fff;font-weight:700;letter-spacing:-.02em">Create account</h1>
<p style="font-size:13px;line-height:1.6;color:#94A3B8">Provisioned access for authorized personnel. All registrations are audited and subject to role review.</p>
<div style="border:1px solid rgba(255,255,255,.1);border-radius:12px;overflow:hidden;background:#0B1224;margin-top:12px">
<div style="height:28px;background:#172033;display:flex;align-items:center;gap:6px;padding:0 10px;font-size:11px;color:#94A3B8"><span style="width:8px;height:8px;border-radius:50%;background:#22C55E"></span> Secure enrollment <span class="ms-auto badge bg-primary" style="font-size:10px">RBAC</span></div>
<div style="padding:14px">
<div class="d-flex align-items-center gap-2 mb-2" style="font-size:12px;color:#E2E8F0"><i class="bi bi-shield-check text-success"></i> System Administrator</div>
<div class="d-flex align-items-center gap-2 mb-2" style="font-size:12px;color:#E2E8F0"><i class="bi bi-person-badge text-primary"></i> Exam Admin · Reviewer · Invigilator</div>
<div class="d-flex align-items-center gap-2" style="font-size:12px;color:#E2E8F0"><i class="bi bi-eye text-warning"></i> Auditor <span class="text-muted" style="font-size:11px">— read-only</span></div>
</div>
</div>
<div class="ill-row">
<span><i class="bi bi-lock text-success"></i> Hashed passwords</span>
<span><i class="bi bi-journal-text text-primary"></i> Audit trail</span>
<span><i class="bi bi-shield-exclamation text-warning"></i> Human oversight</span>
</div>
<ul class="list-unstyled mt-3 mb-0" style="font-size:12px;color:#94A3B8">
<li class="d-flex gap-2 mb-1"><i class="bi bi-check-circle text-success"></i> Institutional email verification</li>
<li class="d-flex gap-2 mb-1"><i class="bi bi-check-circle text-success"></i> Password strength enforcement</li>
<li class="d-flex gap-2"><i class="bi bi-check-circle text-success"></i> Role assigned by administrator</li>
</ul>
<div class="mt-auto pt-3" style="font-size:11px;color:#64748B;border-top:1px solid #1e293b;margin-top:16px;padding-top:12px">
<i class="bi bi-info-circle me-1"></i> If you were not invited, contact your administrator. Public self-registration may be disabled in production.
</div>
</div>
<div class="form-panel">
<div class="d-flex justify-content-between align-items-center mb-3">
<h2 class="h5 mb-0" style="font-weight:700">Register</h2>
<a href="/" class="text-decoration-none" style="font-size:12px"><i class="bi bi-arrow-left me-1"></i> Home</a>
</div>
<p class="text-muted mb-3" style="font-size:13px">Create your account to access the surveillance dashboard.</p>
@if($errors->any())
<div class="alert alert-danger py-2" style="font-size:12px"><i class="bi bi-exclamation-circle me-1"></i> Please review the errors below.</div>
@endif
<form method="POST" action="{{ route('register') }}" novalidate>
@csrf
<div class="mb-3">
<label for="name" class="form-label">Name <span class="text-danger">*</span></label>
<input id="name" type="text" name="name" value="{{ old('name') }}" required autofocus autocomplete="name" class="form-control @error('name') is-invalid @enderror" placeholder="Full name">
@error('name')<div class="invalid-feedback d-block" style="font-size:12px"><i class="bi bi-exclamation-circle me-1"></i>{{ $message }}</div>@enderror
</div>
<div class="mb-3">
<label for="email" class="form-label">Email <span class="text-danger">*</span></label>
<input id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="username" class="form-control @error('email') is-invalid @enderror" placeholder="name@institution.edu">
<div class="form-text" style="font-size:11px">Institutional email only.</div>
@error('email')<div class="invalid-feedback d-block" style="font-size:12px"><i class="bi bi-exclamation-circle me-1"></i>{{ $message }}</div>@enderror
</div>
<div class="mb-3">
<label for="password" class="form-label">Password <span class="text-danger">*</span></label>
<div class="input-group">
<input id="password" type="password" name="password" required autocomplete="new-password" class="form-control @error('password') is-invalid @enderror" placeholder="••••••••" oninput="checkStrength(this.value)">
<button class="btn btn-outline-secondary" type="button" onclick="togglePwd('password',this)" aria-label="Toggle password visibility"><i class="bi bi-eye"></i></button>
</div>
<div class="strength" aria-hidden="true"><div id="strengthBar" class="strength-bar"></div></div>
<div id="strengthText" class="hint">Use 8+ characters with mixed case, number and symbol.</div>
@error('password')<div class="invalid-feedback d-block" style="font-size:12px"><i class="bi bi-exclamation-circle me-1"></i>{{ $message }}</div>@enderror
</div>
<div class="mb-3">
<label for="password_confirmation" class="form-label">Confirm Password <span class="text-danger">*</span></label>
<div class="input-group">
<input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" class="form-control" placeholder="••••••••">
<button class="btn btn-outline-secondary" type="button" onclick="togglePwd('password_confirmation',this)" aria-label="Toggle confirm password visibility"><i class="bi bi-eye"></i></button>
</div>
<div class="hint">Re-enter the same password.</div>
@error('password_confirmation')<div class="invalid-feedback d-block" style="font-size:12px"><i class="bi bi-exclamation-circle me-1"></i>{{ $message }}</div>@enderror
</div>
<button type="submit" class="btn btn-primary w-100" style="font-weight:600"><i class="bi bi-person-plus me-1"></i> Create account</button>
<div class="text-center mt-3" style="font-size:13px"><span class="text-muted">Already registered?</span> <a href="{{ route('login') }}" class="text-decoration-none fw-semibold">Sign in</a></div>
</form>
</div>
</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function togglePwd(id,btn){const i=document.getElementById(id);const icon=btn.querySelector('i');const isPwd=i.type==='password';i.type=isPwd?'text':'password';icon.className=isPwd?'bi bi-eye-slash':'bi bi-eye'}
function checkStrength(v){
 const bar=document.getElementById('strengthBar'),txt=document.getElementById('strengthText');
 let score=0;
 if(v.length>=8)score++; if(/[A-Z]/.test(v)&&/[a-z]/.test(v))score++; if(/[0-9]/.test(v))score++; if(/[^A-Za-z0-9]/.test(v))score++;
 const pct=[ "0%","25%","50%","75%","100%"][score], colors=["#E2E8F0","#EF4444","#F59E0B","#3B82F6","#22C55E"], labels=["Too weak","Weak","Fair","Good","Strong"];
 bar.style.width=pct; bar.style.background=colors[score]; txt.textContent= v.length===0 ? "Use 8+ characters with mixed case, number and symbol." : labels[score] + (score<3 ? " — add length, mixed case, numbers or symbols." : " — good to go.");
 txt.style.color= v.length===0 ? "#64748B" : colors[score];
}
</script>
</body>
</html>
