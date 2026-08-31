<x-guest-layout>
<div class="d-flex justify-content-between align-items-center mb-3">
<h2 class="h5 mb-0" style="font-weight:700">Welcome back</h2>
<a href="/" class="text-decoration-none" style="font-size:12px"><i class="bi bi-arrow-left me-1" aria-hidden="true"></i> Home</a>
</div>
<p class="text-muted mb-3" style="font-size:13px">Sign in to continue to the surveillance dashboard.</p>
<x-auth-session-status class="mb-3" :status="session('status')" />
@if($errors->any())
<div class="alert alert-danger py-2" role="alert" style="font-size:12px"><i class="bi bi-exclamation-circle me-1" aria-hidden="true"></i> Please correct the highlighted fields.</div>
@endif
<form method="POST" action="{{ route('login') }}" novalidate>
@csrf
<div class="mb-3">
<label for="email" class="form-label">Email <span class="text-danger" aria-hidden="true">*</span></label>
<input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username" aria-required="true" aria-describedby="emailHelp" class="form-control @error('email') is-invalid @enderror" placeholder="name@institution.edu">
<div id="emailHelp" class="form-text" style="font-size:11px">Institutional email only.</div>
@error('email')<div class="invalid-feedback d-block" role="alert"><i class="bi bi-exclamation-circle me-1" aria-hidden="true"></i> {{ $message }}</div>@enderror
</div>
<div class="mb-3">
<label for="password" class="form-label">Password <span class="text-danger" aria-hidden="true">*</span></label>
<div class="input-group">
<input id="password" type="password" name="password" required autocomplete="current-password" aria-required="true" aria-describedby="passwordHelp" class="form-control @error('password') is-invalid @enderror" placeholder="••••••••">
<button class="btn btn-outline-secondary" type="button" aria-label="Show password" aria-pressed="false" onclick="togglePassword('password', this)"><i class="bi bi-eye" aria-hidden="true"></i></button>
</div>
<div id="passwordHelp" class="form-text" style="font-size:11px">Use your assigned password. Passwords are hashed.</div>
@error('password')<div class="invalid-feedback d-block" role="alert"><i class="bi bi-exclamation-circle me-1" aria-hidden="true"></i> {{ $message }}</div>@enderror
</div>
<div class="d-flex justify-content-between align-items-center mb-3">
<label for="remember_me" class="d-flex align-items-center gap-2 m-0" style="font-size:13px;cursor:pointer">
<input id="remember_me" type="checkbox" name="remember" class="form-check-input m-0"> Remember me
</label>
@if(Route::has('password.request'))
<a href="{{ route('password.request') }}" class="text-decoration-none" style="font-size:13px">Forgot password?</a>
@endif
</div>
<button type="submit" class="btn btn-primary w-100" style="font-weight:600"><i class="bi bi-box-arrow-in-right me-1" aria-hidden="true"></i> Log in</button>
@if(Route::has('register'))
<div class="text-center mt-3" style="font-size:13px"><span class="text-muted">Need an account?</span> <a href="{{ route('register') }}" class="text-decoration-none fw-semibold">Create account</a> <span class="text-muted" style="font-size:11px">· Research prototype</span></div>
@else
<p class="text-center text-muted mt-3 mb-0" style="font-size:11px"><i class="bi bi-lock me-1" aria-hidden="true"></i> Accounts are created by an authorized administrator.</p>
@endif
</form>
</x-guest-layout>
