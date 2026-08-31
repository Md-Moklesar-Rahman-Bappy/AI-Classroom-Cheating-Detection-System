<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>{{ config('app.name', 'AI Classroom') }} — {{ $title ?? 'Authentication' }}</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
:root{--primary:#2563EB;--primary-dark:#1D4ED8;--sidebar:#0F172A;--sidebar-elevated:#172033;--border:#E2E8F0;--text:#172033;--muted:#64748B;--bg:#F8FAFC;--surface:#FFFFFF;--success:#16A34A;--warning:#D97706;--danger:#DC2626;--radius-md:12px;--radius-lg:16px;--shadow-md:0 4px 12px rgba(15,23,42,.08)}
*{font-family:Inter,system-ui,sans-serif}
body{background:var(--bg);min-height:100vh;display:flex;align-items:center;justify-content:center;padding:16px;color:var(--text);margin:0}
.skip-link{position:absolute;top:-40px;left:8px;background:var(--primary);color:#fff;padding:8px 12px;border-radius:8px;z-index:9999;text-decoration:none;font-size:13px}
.skip-link:focus{top:8px}
.guest-wrap{max-width:1000px;width:100%;display:grid;grid-template-columns:1fr 1fr;border:1px solid var(--border);border-radius:var(--radius-lg);overflow:hidden;background:var(--surface);box-shadow:var(--shadow-md)}
.brand-panel{background:var(--sidebar);color:#CBD5E1;padding:28px;display:flex;flex-direction:column;min-width:0}
.form-panel{padding:28px;min-width:0;background:var(--surface)}
.form-label{font-size:11px;letter-spacing:.06em;text-transform:uppercase;color:var(--muted);font-weight:600;margin-bottom:4px}
.form-control{height:38px;border-color:var(--border);font-size:14px}
.form-control:focus{box-shadow:0 0 0 3px rgba(37,99,235,.15);border-color:var(--primary)}
.btn-primary{background:var(--primary);border-color:var(--primary);font-weight:600;min-height:38px}
.btn-primary:hover{background:var(--primary-dark);border-color:var(--primary-dark)}
.btn-outline-secondary{min-height:38px}
.invalid-feedback{font-size:12px}
.ill-row{display:flex;gap:8px;flex-wrap:wrap;margin-top:12px}
.ill-row span{font-size:11px;background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.08);border-radius:999px;padding:4px 8px;display:inline-flex;align-items:center;gap:4px}
.illustration{border:1px solid rgba(255,255,255,.1);border-radius:12px;overflow:hidden;background:#0B1224;margin-top:16px;max-width:100%;height:auto;overflow:hidden}
.illustration *{max-width:100%}
.surveillance-inner{height:130px;background:linear-gradient(135deg,#1e293b,#0f172a);position:relative;display:flex;align-items:center;justify-content:center;overflow:hidden;max-width:100%}
@media(max-width:767.98px){.guest-wrap{grid-template-columns:1fr}.brand-panel{padding:20px}.form-panel{padding:20px}}
@media(prefers-reduced-motion:reduce){*{transition:none!important;animation:none!important}}
</style>
</head>
<body>
<a href="#main-content" class="skip-link">Skip to content</a>
<main id="main-content" class="guest-wrap" tabindex="-1" aria-labelledby="guest-heading">
<div class="brand-panel">
<div class="d-flex align-items-center gap-2 mb-3">
<span class="bg-primary rounded d-flex align-items-center justify-content-center flex-shrink-0" style="width:36px;height:36px" aria-hidden="true"><i class="bi bi-shield-lock text-white" style="font-size:18px"></i></span>
<div style="min-width:0"><div style="color:#fff;font-weight:700;letter-spacing:-.02em;line-height:1">AI Classroom</div><div style="font-size:10px;letter-spacing:.08em;text-transform:uppercase;color:#94A3B8">Surveillance Platform</div></div>
</div>
<h1 id="guest-heading" class="h5 mb-2" style="color:#fff;font-weight:700;letter-spacing:-.02em">AI Classroom Cheating Detection System</h1>
<p style="font-size:13px;line-height:1.6;color:#94A3B8">AI-assisted recorded and live examination monitoring with protected evidence and authorized human review. Research prototype.</p>
<div class="illustration" aria-hidden="true">
<div style="height:28px;background:var(--sidebar-elevated);display:flex;align-items:center;gap:6px;padding:0 10px;font-size:11px;color:#94A3B8"><span style="width:8px;height:8px;border-radius:50%;background:var(--danger)"></span><span style="width:8px;height:8px;border-radius:50%;background:#EAB308"></span><span style="width:8px;height:8px;border-radius:50%;background:var(--success)"></span><i class="bi bi-camera-video ms-2" aria-hidden="true"></i> AI surveillance · YOLO11n · ByteTrack</div>
<div class="surveillance-inner">
<div style="position:absolute;left:14%;top:22%;width:22%;height:56%;border:2px solid var(--success);border-radius:6px"><span class="badge bg-success" style="font-size:8px;position:absolute;top:-10px;left:0">person 0.92</span></div>
<div style="position:absolute;left:44%;top:18%;width:20%;height:52%;border:2px solid var(--success);border-radius:6px"><span class="badge bg-success" style="font-size:8px;position:absolute;top:-10px;left:0">person 0.88</span></div>
<div style="position:absolute;right:18%;top:26%;width:18%;height:44%;border:2px solid #F59E0B;border-radius:6px"><span class="badge bg-warning text-dark" style="font-size:7px;position:absolute;top:-10px;left:0">B1 review</span></div>
<div style="position:absolute;left:50%;transform:translateX(-50%);bottom:10px;background:rgba(15,23,42,.9);border:1px solid rgba(255,255,255,.1);border-radius:999px;padding:3px 8px;font-size:10px;color:#E2E8F0"><i class="bi bi-activity text-warning me-1" aria-hidden="true"></i> 24 FPS · Evidence preserved</div>
</div>
</div>
<div class="ill-row" aria-hidden="true">
<span><i class="bi bi-person-bounding-box text-success" aria-hidden="true"></i> Person</span>
<span><i class="bi bi-phone text-danger" aria-hidden="true"></i> Phone D2</span>
<span><i class="bi bi-eye text-warning" aria-hidden="true"></i> Orientation B1-B4</span>
<span><i class="bi bi-journal-text text-primary" aria-hidden="true"></i> Evidence</span>
</div>
<ul class="list-unstyled mt-3 mb-0" style="font-size:12px;color:#94A3B8">
<li class="d-flex gap-2 mb-1"><i class="bi bi-shield-check text-success" aria-hidden="true"></i> Encrypted credentials • Private storage • Audit trail</li>
<li class="d-flex gap-2 mb-1"><i class="bi bi-people text-primary" aria-hidden="true"></i> Roles: System Admin · Exam Admin · Reviewer · Invigilator · Auditor</li>
<li class="d-flex gap-2"><i class="bi bi-lock text-success" aria-hidden="true"></i> Protected evidence • No public participant data</li>
</ul>
<div class="mt-auto pt-3" style="font-size:11px;color:#64748B;border-top:1px solid #1e293b;margin-top:16px;padding-top:12px">
<div class="d-flex gap-2 mb-2"><i class="bi bi-shield-exclamation text-warning" aria-hidden="true"></i><div><strong style="color:#CBD5E1">Responsible AI:</strong> Alerts indicate observable events requiring human review — not proof of misconduct.</div></div>
<a href="/" class="text-decoration-none" style="font-size:12px;color:#93C5FD"><i class="bi bi-house me-1" aria-hidden="true"></i> Back to home</a>
</div>
</div>
<div class="form-panel">
{{ $slot }}
</div>
</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function togglePassword(inputId, button){
  const input = document.getElementById(inputId);
  const icon = button.querySelector('i');
  if(!input || !icon) return;
  const isPassword = input.type === 'password';
  input.type = isPassword ? 'text' : 'password';
  icon.className = isPassword ? 'bi bi-eye-slash' : 'bi bi-eye';
  button.setAttribute('aria-label', isPassword ? 'Hide password' : 'Show password');
  button.setAttribute('aria-pressed', isPassword ? 'true' : 'false');
}
</script>
</body>
</html>
