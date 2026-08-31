<x-guest-layout>
<div class="d-flex justify-content-between align-items-center mb-3">
<h2 class="h5 mb-0" style="font-weight:700">Verify email</h2>
<a href="/" class="text-decoration-none" style="font-size:12px"><i class="bi bi-house me-1" aria-hidden="true"></i> Home</a>
</div>
<p class="text-muted mb-3" style="font-size:13px">Thanks for signing up — verification confirms you control this institutional email before dashboard access is granted. Check your inbox for the link.</p>
@if (session('status') == 'verification-link-sent')
<div class="alert alert-success py-2" style="font-size:12px"><i class="bi bi-check-circle me-1" aria-hidden="true"></i> A new verification link has been sent to the email address you provided.</div>
@endif
<div class="card p-3 mb-3" style="background:#F8FAFC;border:1px solid #E2E8F0;font-size:13px">
<div class="d-flex gap-2"><i class="bi bi-info-circle text-primary mt-1" aria-hidden="true"></i><div><div style="font-weight:600">Why verification is required</div><div class="text-muted" style="font-size:12px">Prevents unauthorized registration and ensures audit trail ownership. You’ll be redirected to login after verification.</div></div></div>
</div>
<div class="d-flex flex-column gap-2">
<form method="POST" action="{{ route('verification.send') }}">
@csrf
<button type="submit" class="btn btn-primary w-100" style="font-weight:600"><i class="bi bi-envelope me-1" aria-hidden="true"></i> Resend verification email</button>
</form>
<form method="POST" action="{{ route('logout') }}">
@csrf
<button type="submit" class="btn btn-outline-secondary w-100"><i class="bi bi-box-arrow-right me-1" aria-hidden="true"></i> Log out</button>
</form>
</div>
<p class="text-muted mt-3 mb-0" style="font-size:11px"><i class="bi bi-shield-check me-1" aria-hidden="true"></i> Links expire. If you didn’t request an account, you may ignore the email.</p>
</x-guest-layout>
