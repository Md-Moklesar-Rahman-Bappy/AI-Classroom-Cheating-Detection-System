<x-guest-layout>
<div class="d-flex justify-content-between align-items-center mb-3">
<h2 class="h5 mb-0" style="font-weight:700">Reset password</h2>
<a href="{{ route('login') }}" class="text-decoration-none" style="font-size:12px"><i class="bi bi-arrow-left me-1" aria-hidden="true"></i> Back to login</a>
</div>
<p class="text-muted mb-3" style="font-size:13px">Choose a new password. It must meet the requirements below — server validation is authoritative.</p>
<form method="POST" action="{{ route('password.store') }}" novalidate>
@csrf
<input type="hidden" name="token" value="{{ $request->route('token') }}">
<div class="mb-3">
<label for="email" class="form-label">Email <span class="text-danger" aria-hidden="true">*</span></label>
<input id="email" type="email" name="email" value="{{ old('email', $request->email) }}" required autofocus autocomplete="username" class="form-control @error('email') is-invalid @enderror" placeholder="name@institution.edu">
@error('email')<div class="invalid-feedback d-block" style="font-size:12px"><i class="bi bi-exclamation-circle me-1" aria-hidden="true"></i> {{ $message }}</div>@enderror
</div>
<div class="mb-3">
<label for="password" class="form-label">New password <span class="text-danger" aria-hidden="true">*</span></label>
<div class="input-group">
<input id="password" type="password" name="password" required autocomplete="new-password" aria-describedby="passwordHelp" class="form-control @error('password') is-invalid @enderror" placeholder="••••••••" oninput="checkStrength(this.value)">
<button class="btn btn-outline-secondary" type="button" aria-label="Show password" aria-pressed="false" onclick="togglePassword('password', this)"><i class="bi bi-eye" aria-hidden="true"></i></button>
</div>
<div id="passwordHelp" class="form-text" style="font-size:11px">Minimum 8 characters; include letters, numbers and symbols.</div>
<div class="mt-2" aria-hidden="true" style="height:6px;background:#E2E8F0;border-radius:999px;overflow:hidden"><div id="strengthBar" style="height:100%;width:0%;transition:width .2s,background .2s;border-radius:999px"></div></div>
<div id="strengthText" style="font-size:11px;color:#64748B" aria-live="polite" class="mt-1">Use 8+ characters with mixed case, number and symbol.</div>
@error('password')<div class="invalid-feedback d-block" style="font-size:12px"><i class="bi bi-exclamation-circle me-1" aria-hidden="true"></i> {{ $message }}</div>@enderror
</div>
<div class="mb-3">
<label for="password_confirmation" class="form-label">Confirm password <span class="text-danger" aria-hidden="true">*</span></label>
<div class="input-group">
<input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" class="form-control" placeholder="••••••••" oninput="checkMatch()">
<button class="btn btn-outline-secondary" type="button" aria-label="Show password" aria-pressed="false" onclick="togglePassword('password_confirmation', this)"><i class="bi bi-eye" aria-hidden="true"></i></button>
</div>
<div id="matchText" style="font-size:11px;color:#64748B" aria-live="polite" class="mt-1">Re-enter the same password.</div>
@error('password_confirmation')<div class="invalid-feedback d-block" style="font-size:12px"><i class="bi bi-exclamation-circle me-1" aria-hidden="true"></i> {{ $message }}</div>@enderror
</div>
<button type="submit" class="btn btn-primary w-100" style="font-weight:600"><i class="bi bi-shield-lock me-1" aria-hidden="true"></i> Reset password</button>
</form>
<script>
function checkStrength(v){
  const bar=document.getElementById('strengthBar'),txt=document.getElementById('strengthText');
  let s=0; if(v.length>=8)s++; if(/[A-Z]/.test(v)&&/[a-z]/.test(v))s++; if(/[0-9]/.test(v))s++; if(/[^A-Za-z0-9]/.test(v))s++;
  const pct=["0%","25%","50%","75%","100%"][s], col=["#E2E8F0","#EF4444","#F59E0B","#3B82F6","#16A34A"], lab=["Too weak","Weak","Fair","Good","Strong"];
  if(bar){bar.style.width=pct;bar.style.background=col[s];}
  if(txt){txt.textContent=v.length===0?"Use 8+ characters with mixed case, number and symbol.":lab[s]; txt.style.color=col[s];}
  checkMatch();
}
function checkMatch(){const p=document.getElementById('password'),c=document.getElementById('password_confirmation'),t=document.getElementById('matchText'); if(!p||!c||!t)return; if(c.value.length===0){t.textContent="Re-enter the same password.";t.style.color="#64748B";} else if(p.value===c.value){t.textContent="✓ Passwords match.";t.style.color="#16A34A";} else {t.textContent="✗ Passwords do not match.";t.style.color="#DC2626";}}
</script>
</x-guest-layout>
