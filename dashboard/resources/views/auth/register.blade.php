<x-guest-layout>
<div class="d-flex justify-content-between align-items-center mb-3">
<h2 class="h5 mb-0" style="font-weight:700">Create account</h2>
<a href="/" class="text-decoration-none" style="font-size:12px"><i class="bi bi-arrow-left me-1" aria-hidden="true"></i> Home</a>
</div>
<p class="text-muted mb-3" style="font-size:13px">Research prototype — registration creates a low-privilege account pending administrator approval.</p>
@if($errors->any())
<div class="alert alert-danger py-2" style="font-size:12px" role="alert"><i class="bi bi-exclamation-circle me-1" aria-hidden="true"></i> Please review the errors below.</div>
@endif
<form method="POST" action="{{ route('register') }}" novalidate>
@csrf
<div class="mb-3">
<label for="name" class="form-label">Full name <span class="text-danger" aria-hidden="true">*</span></label>
<input id="name" type="text" name="name" value="{{ old('name') }}" required autofocus autocomplete="name" class="form-control @error('name') is-invalid @enderror" placeholder="Full name">
@error('name')<div class="invalid-feedback d-block" style="font-size:12px"><i class="bi bi-exclamation-circle me-1" aria-hidden="true"></i> {{ $message }}</div>@enderror
</div>
<div class="mb-3">
<label for="email" class="form-label">Email <span class="text-danger" aria-hidden="true">*</span></label>
<input id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="username" class="form-control @error('email') is-invalid @enderror" placeholder="name@institution.edu">
<div class="form-text" style="font-size:11px">Institutional email only.</div>
@error('email')<div class="invalid-feedback d-block" style="font-size:12px"><i class="bi bi-exclamation-circle me-1" aria-hidden="true"></i> {{ $message }}</div>@enderror
</div>
<div class="mb-3">
<label for="password" class="form-label">Password <span class="text-danger" aria-hidden="true">*</span></label>
<div class="input-group">
<input id="password" type="password" name="password" required autocomplete="new-password" aria-describedby="passwordHelp strengthText" class="form-control @error('password') is-invalid @enderror" placeholder="••••••••" oninput="checkStrength(this.value)">
<button class="btn btn-outline-secondary" type="button" aria-label="Show password" aria-pressed="false" onclick="togglePassword('password', this)"><i class="bi bi-eye" aria-hidden="true"></i></button>
</div>
<div id="passwordHelp" class="form-text" style="font-size:11px">At least 8 characters; guidance aligns with server rules (letters, numbers, symbols).</div>
<div class="strength mt-2" aria-hidden="true" style="height:6px;background:#E2E8F0;border-radius:999px;overflow:hidden"><div id="strengthBar" style="height:100%;width:0%;transition:width .2s,background .2s;border-radius:999px"></div></div>
<div id="strengthText" class="mt-1" style="font-size:11px;color:#64748B" aria-live="polite">Use 8+ characters with mixed case, number and symbol.</div>
<div id="passwordReq" class="mt-1 p-2 rounded" style="background:#F8FAFC;border:1px solid #E2E8F0;font-size:11px">
<div style="font-weight:600;font-size:11px;letter-spacing:.06em;text-transform:uppercase;color:#64748B">Password requirements (server truth)</div>
<ul class="mb-0 mt-1" style="line-height:1.6;color:#64748B">
<li id="reqLen"><i class="bi bi-x-circle me-1" aria-hidden="true"></i> Minimum 8 characters</li>
<li id="reqCase"><i class="bi bi-x-circle me-1" aria-hidden="true"></i> Upper and lower case letters</li>
<li id="reqNum"><i class="bi bi-x-circle me-1" aria-hidden="true"></i> At least one number</li>
<li id="reqSym"><i class="bi bi-x-circle me-1" aria-hidden="true"></i> At least one symbol</li>
</ul>
<div style="font-size:10px;color:#94A3B8" class="mt-1">Strength is client-side guidance only — server validation is authoritative.</div>
</div>
@error('password')<div class="invalid-feedback d-block" style="font-size:12px"><i class="bi bi-exclamation-circle me-1" aria-hidden="true"></i> {{ $message }}</div>@enderror
</div>
<div class="mb-3">
<label for="password_confirmation" class="form-label">Confirm password <span class="text-danger" aria-hidden="true">*</span></label>
<div class="input-group">
<input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" aria-describedby="matchText" class="form-control" placeholder="••••••••" oninput="checkMatch()">
<button class="btn btn-outline-secondary" type="button" aria-label="Show password" aria-pressed="false" onclick="togglePassword('password_confirmation', this)"><i class="bi bi-eye" aria-hidden="true"></i></button>
</div>
<div id="matchText" style="font-size:11px;color:#64748B" aria-live="polite" class="mt-1">Re-enter the same password.</div>
@error('password_confirmation')<div class="invalid-feedback d-block" style="font-size:12px"><i class="bi bi-exclamation-circle me-1" aria-hidden="true"></i> {{ $message }}</div>@enderror
</div>
<button type="submit" class="btn btn-primary w-100" style="font-weight:600"><i class="bi bi-person-plus me-1" aria-hidden="true"></i> Create account</button>
<div class="text-center mt-3" style="font-size:13px"><span class="text-muted">Already registered?</span> <a href="{{ route('login') }}" class="text-decoration-none fw-semibold">Sign in</a></div>
<p class="text-center text-muted mt-2 mb-0" style="font-size:11px">New accounts receive no privileged role. Email verification required before dashboard access. Administrator assigns role.</p>
</form>
<script>
function checkStrength(v){
  const bar=document.getElementById('strengthBar'),txt=document.getElementById('strengthText');
  let score=0;
  const hasLen=v.length>=8,hasCase=/[A-Z]/.test(v)&&/[a-z]/.test(v),hasNum=/[0-9]/.test(v),hasSym=/[^A-Za-z0-9]/.test(v);
  if(hasLen)score++; if(hasCase)score++; if(hasNum)score++; if(hasSym)score++;
  const pct=["0%","25%","50%","75%","100%"][score], colors=["#E2E8F0","#EF4444","#F59E0B","#3B82F6","#16A34A"], labels=["Too weak","Weak","Fair","Good","Strong"];
  if(bar){bar.style.width=pct; bar.style.background=colors[score];}
  if(txt){txt.textContent= v.length===0 ? "Use 8+ characters with mixed case, number and symbol." : labels[score] + (score<3 ? " — add length, mixed case, numbers or symbols." : " — good to go."); txt.style.color= v.length===0 ? "#64748B" : colors[score];}
  const set=(id,ok)=>{const el=document.getElementById(id); if(!el) return; const i=el.querySelector('i'); if(i) i.className= ok ? 'bi bi-check-circle-fill text-success me-1' : 'bi bi-x-circle me-1'; el.style.color= ok ? '#16A34A' : '#64748B';};
  set('reqLen',hasLen); set('reqCase',hasCase); set('reqNum',hasNum); set('reqSym',hasSym);
  checkMatch();
}
function checkMatch(){
  const p=document.getElementById('password'),c=document.getElementById('password_confirmation'),t=document.getElementById('matchText');
  if(!p||!c||!t) return;
  if(c.value.length===0){t.textContent="Re-enter the same password."; t.style.color="#64748B";}
  else if(p.value===c.value){t.textContent="✓ Passwords match."; t.style.color="#16A34A";}
  else {t.textContent="✗ Passwords do not match."; t.style.color="#DC2626";}
}
</script>
</x-guest-layout>
