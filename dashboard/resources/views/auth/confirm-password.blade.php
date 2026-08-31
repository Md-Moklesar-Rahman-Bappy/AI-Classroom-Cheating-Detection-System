<x-guest-layout>
<div class="d-flex justify-content-between align-items-center mb-3">
<h2 class="h5 mb-0" style="font-weight:700">Confirm password</h2>
<a href="{{ route('dashboard') }}" class="text-decoration-none" style="font-size:12px"><i class="bi bi-speedometer2 me-1" aria-hidden="true"></i> Dashboard</a>
</div>
<p class="text-muted mb-3" style="font-size:13px">This is a secure area. Please confirm your password before continuing.</p>
<form method="POST" action="{{ route('password.confirm') }}" novalidate>
@csrf
<div class="mb-3">
<label for="password" class="form-label">Password <span class="text-danger" aria-hidden="true">*</span></label>
<div class="input-group">
<input id="password" type="password" name="password" required autocomplete="current-password" class="form-control @error('password') is-invalid @enderror" placeholder="••••••••">
<button class="btn btn-outline-secondary" type="button" aria-label="Show password" aria-pressed="false" onclick="togglePassword('password', this)"><i class="bi bi-eye" aria-hidden="true"></i></button>
</div>
@error('password')<div class="invalid-feedback d-block" style="font-size:12px"><i class="bi bi-exclamation-circle me-1" aria-hidden="true"></i> {{ $message }}</div>@enderror
</div>
<button type="submit" class="btn btn-primary w-100" style="font-weight:600"><i class="bi bi-check-lg me-1" aria-hidden="true"></i> Confirm</button>
</form>
</x-guest-layout>
