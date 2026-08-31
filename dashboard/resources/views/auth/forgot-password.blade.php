<x-guest-layout>
<div class="d-flex justify-content-between align-items-center mb-3">
<h2 class="h5 mb-0" style="font-weight:700">Forgot password</h2>
<a href="{{ route('login') }}" class="text-decoration-none" style="font-size:12px"><i class="bi bi-arrow-left me-1" aria-hidden="true"></i> Back to login</a>
</div>
<p class="text-muted mb-3" style="font-size:13px">Enter your email and we’ll send a secure password reset link. Links expire and are single-use.</p>
<x-auth-session-status class="mb-3" :status="session('status')" />
<form method="POST" action="{{ route('password.email') }}" novalidate>
@csrf
<div class="mb-3">
<label for="email" class="form-label">Email <span class="text-danger" aria-hidden="true">*</span></label>
<input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus class="form-control @error('email') is-invalid @enderror" placeholder="name@institution.edu" autocomplete="username" aria-describedby="emailHelp">
<div id="emailHelp" class="form-text" style="font-size:11px">We’ll email a reset link if the address is registered.</div>
@error('email')<div class="invalid-feedback d-block" role="alert" style="font-size:12px"><i class="bi bi-exclamation-circle me-1" aria-hidden="true"></i> {{ $message }}</div>@enderror
</div>
<button type="submit" class="btn btn-primary w-100" style="font-weight:600"><i class="bi bi-envelope me-1" aria-hidden="true"></i> Email password reset link</button>
<div class="text-center mt-3" style="font-size:13px"><a href="/" class="text-decoration-none"><i class="bi bi-house me-1" aria-hidden="true"></i> Back to home</a></div>
</form>
</x-guest-layout>
