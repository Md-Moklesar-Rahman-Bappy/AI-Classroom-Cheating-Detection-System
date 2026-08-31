@extends("layouts.bootstrap")
@section("title","Profile")
@section("content")
@php
$user = $user ?? auth()->user();
$roleName = $user->roles->first()?->name ?? 'No Role';
$roleDesc = $user->roles->first()?->description ?? $roleName;
$createdJobs = \App\Models\AnalysisJob::where('created_by', $user->id)->count();
$reviews = \App\Models\ReviewDecision::where('reviewed_by', $user->id)->count();
$auditReports = \App\Models\AuditLog::where('actor_id', $user->id)->count();
$isVerified = !is_null($user->email_verified_at);
@endphp
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
<div><h2 class="mb-1" style="font-weight:700;letter-spacing:-.02em">Profile</h2><p class="text-muted mb-0" style="font-size:13px">Manage your account information and security</p></div>
<a href="{{ route('dashboard') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i> Back to dashboard</a>
</div>
@if(session('status')==='profile-updated')<div class="alert alert-success py-2 d-flex align-items-center gap-2" style="font-size:13px"><i class="bi bi-check-circle"></i> Profile updated successfully.</div>@endif
@if(session('status')==='password-updated')<div class="alert alert-success py-2 d-flex align-items-center gap-2" style="font-size:13px"><i class="bi bi-check-circle"></i> Password updated successfully.</div>@endif
<div class="card mb-4 overflow-hidden" style="border:1px solid #E2E8F0;border-radius:12px">
<div style="height:72px;background:linear-gradient(135deg,#0F172A 0%,#1e293b 60%,#1D4ED8 100%)"></div>
<div class="card-body" style="padding:16px 20px">
<div class="d-flex flex-column flex-md-row gap-3 align-items-start">
<div class="d-flex align-items-center justify-content-center rounded-circle flex-shrink-0" style="width:72px;height:72px;background:#2563EB;color:#fff;font-weight:700;font-size:24px;margin-top:-40px;border:4px solid #fff;box-shadow:0 4px 12px rgba(15,23,42,.12)">{{ strtoupper(substr($user->name,0,1)) }}</div>
<div class="flex-grow-1" style="min-width:0">
<div class="d-flex flex-wrap align-items-center gap-2">
<h3 class="mb-0" style="font-size:18px;font-weight:700;letter-spacing:-.02em">{{ $user->name }}</h3>
<span class="badge" style="background:#DBEAFE;color:#1D4ED8;font-size:11px;letter-spacing:.02em"><i class="bi bi-shield-check me-1"></i> {{ ucwords(str_replace('_',' ',$roleName)) }}</span>
@if($isVerified)<span class="badge bg-success" style="font-size:11px"><i class="bi bi-patch-check me-1"></i> Verified</span>@else<span class="badge bg-warning text-dark" style="font-size:11px"><i class="bi bi-exclamation-triangle me-1"></i> Unverified</span>@endif
<span class="badge bg-light text-dark border" style="font-size:11px"><i class="bi bi-circle-fill text-success me-1" style="font-size:7px"></i> Account Active</span>
</div>
<div class="text-muted mt-1" style="font-size:13px"><i class="bi bi-envelope me-1"></i> {{ $user->email }}</div>
<div class="text-muted" style="font-size:11px">Member since {{ $user->created_at?->format('M d, Y') }} · ID #{{ $user->id }}</div>
</div>
<div class="d-flex gap-2 ms-md-auto">
<a href="#profile-info" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil me-1"></i> Edit</a>
<a href="#security" class="btn btn-sm btn-outline-secondary"><i class="bi bi-shield-lock me-1"></i> Security</a>
</div>
</div>
</div>
</div>
<div class="row g-3 mb-4">
<div class="col-12 col-md-4"><div class="card p-3 h-100"><div class="d-flex justify-content-between align-items-center"><div><div class="text-muted" style="font-size:11px;letter-spacing:.08em;text-transform:uppercase">Created Jobs</div><div style="font-size:22px;font-weight:700">{{ $createdJobs }}</div><div class="text-muted" style="font-size:12px">Analysis jobs created</div></div><div class="d-flex align-items-center justify-content-center rounded" style="width:36px;height:36px;background:#DBEAFE;color:#2563EB"><i class="bi bi-cpu"></i></div></div></div></div>
<div class="col-12 col-md-4"><div class="card p-3 h-100"><div class="d-flex justify-content-between align-items-center"><div><div class="text-muted" style="font-size:11px;letter-spacing:.08em;text-transform:uppercase">Reviews</div><div style="font-size:22px;font-weight:700">{{ $reviews }}</div><div class="text-muted" style="font-size:12px">Decisions submitted</div></div><div class="d-flex align-items-center justify-content-center rounded" style="width:36px;height:36px;background:#FEF3C7;color:#D97706"><i class="bi bi-eye"></i></div></div></div></div>
<div class="col-12 col-md-4"><div class="card p-3 h-100"><div class="d-flex justify-content-between align-items-center"><div><div class="text-muted" style="font-size:11px;letter-spacing:.08em;text-transform:uppercase">Audit Activity</div><div style="font-size:22px;font-weight:700">{{ $auditReports }}</div><div class="text-muted" style="font-size:12px">Logged actions</div></div><div class="d-flex align-items-center justify-content-center rounded" style="width:36px;height:36px;background:#DCFCE7;color:#16A34A"><i class="bi bi-journal-text"></i></div></div></div></div>
</div>
<div class="row g-4">
<div class="col-12 col-lg-8">
<div id="profile-info" class="card mb-4">
<div class="card-header bg-white d-flex justify-content-between align-items-center"><h5 class="mb-0" style="font-size:13px;letter-spacing:.06em;text-transform:uppercase"><i class="bi bi-person me-2 text-primary"></i> Profile Information</h5><span class="badge bg-light text-dark border" style="font-size:11px">Editable</span></div>
<div class="card-body">
<p class="text-muted mb-3" style="font-size:13px">Update your account's profile information and email address. Changing email requires re-verification.</p>
<form id="send-verification" method="post" action="{{ route('verification.send') }}">@csrf</form>
<form method="post" action="{{ route('profile.update') }}" novalidate>
@csrf
@method('patch')
<div class="mb-3">
<label for="name" class="form-label" style="font-size:11px;letter-spacing:.06em;text-transform:uppercase;color:#64748B;font-weight:600">Name <span class="text-danger">*</span></label>
<input id="name" name="name" type="text" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $user->name) }}" required autocomplete="name" placeholder="Full name">
@error('name')<div class="invalid-feedback d-block" style="font-size:12px"><i class="bi bi-exclamation-circle me-1"></i>{{ $message }}</div>@enderror
</div>
<div class="mb-3">
<label for="email" class="form-label" style="font-size:11px;letter-spacing:.06em;text-transform:uppercase;color:#64748B;font-weight:600">Email <span class="text-danger">*</span></label>
<input id="email" name="email" type="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $user->email) }}" required autocomplete="username" placeholder="name@institution.edu">
@error('email')<div class="invalid-feedback d-block" style="font-size:12px"><i class="bi bi-exclamation-circle me-1"></i>{{ $message }}</div>@enderror
@if($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
<div class="alert alert-warning py-2 mt-2 mb-0" style="font-size:12px"><i class="bi bi-exclamation-triangle me-1"></i> Your email address is unverified. <button form="send-verification" class="btn btn-sm btn-outline-warning ms-1" style="font-size:11px">Resend verification email</button>
@if(session('status')==='verification-link-sent')<div class="text-success mt-1" style="font-size:12px"><i class="bi bi-check-circle me-1"></i> A new verification link has been sent to your email address.</div>@endif
</div>
@endif
</div>
<div class="d-flex align-items-center gap-3">
<button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i> Save</button>
@if(session('status')==='profile-updated')<span class="text-success" style="font-size:13px"><i class="bi bi-check-circle me-1"></i> Saved.</span>@endif
</div>
</form>
</div>
</div>
<div id="security" class="card mb-4">
<div class="card-header bg-white d-flex justify-content-between align-items-center"><h5 class="mb-0" style="font-size:13px;letter-spacing:.06em;text-transform:uppercase"><i class="bi bi-shield-lock me-2 text-success"></i> Security</h5><span class="badge bg-success" style="font-size:11px"><i class="bi bi-lock me-1"></i> Protected</span></div>
<div class="card-body">
<div class="row g-3" style="font-size:13px">
<div class="col-sm-6"><div class="d-flex gap-2"><i class="bi bi-envelope-check text-primary mt-1"></i><div><div style="font-weight:600">Email verification</div><div class="text-muted" style="font-size:12px">@if($isVerified) Verified on {{ $user->email_verified_at->format('M d, Y') }} @else Not verified — please verify @endif</div></div></div></div>
<div class="col-sm-6"><div class="d-flex gap-2"><i class="bi bi-key text-warning mt-1"></i><div><div style="font-weight:600">Password</div><div class="text-muted" style="font-size:12px">Last updated {{ $user->updated_at->diffForHumans() }} · Use a long random password</div></div></div></div>
<div class="col-sm-6"><div class="d-flex gap-2"><i class="bi bi-person-badge text-info mt-1"></i><div><div style="font-weight:600">Role</div><div class="text-muted" style="font-size:12px">{{ $roleDesc }}</div></div></div></div>
<div class="col-sm-6"><div class="d-flex gap-2"><i class="bi bi-journal-text text-success mt-1"></i><div><div style="font-weight:600">Audit</div><div class="text-muted" style="font-size:12px">All profile changes are logged</div></div></div></div>
</div>
</div>
</div>
</div>
<div class="col-12 col-lg-4">
<div class="card mb-4">
<div class="card-header bg-white"><h5 class="mb-0" style="font-size:13px;letter-spacing:.06em;text-transform:uppercase"><i class="bi bi-key me-2 text-warning"></i> Update Password</h5></div>
<div class="card-body">
<p class="text-muted mb-3" style="font-size:12px">Ensure your account is using a long, random password to stay secure.</p>
<form method="post" action="{{ route('password.update') }}" novalidate>
@csrf
@method('put')
<div class="mb-3">
<label for="update_password_current_password" class="form-label" style="font-size:11px;letter-spacing:.06em;text-transform:uppercase;color:#64748B;font-weight:600">Current Password</label>
<div class="input-group">
<input id="update_password_current_password" name="current_password" type="password" class="form-control" autocomplete="current-password" placeholder="••••••••">
<button class="btn btn-outline-secondary" type="button" onclick="togglePwd('update_password_current_password',this)" aria-label="Toggle current password"><i class="bi bi-eye"></i></button>
</div>
@error('current_password','updatePassword')<div class="text-danger mt-1" style="font-size:12px"><i class="bi bi-exclamation-circle me-1"></i>{{ $message }}</div>@enderror
</div>
<div class="mb-3">
<label for="update_password_password" class="form-label" style="font-size:11px;letter-spacing:.06em;text-transform:uppercase;color:#64748B;font-weight:600">New Password</label>
<div class="input-group">
<input id="update_password_password" name="password" type="password" class="form-control" autocomplete="new-password" placeholder="••••••••">
<button class="btn btn-outline-secondary" type="button" onclick="togglePwd('update_password_password',this)" aria-label="Toggle new password"><i class="bi bi-eye"></i></button>
</div>
@error('password','updatePassword')<div class="text-danger mt-1" style="font-size:12px"><i class="bi bi-exclamation-circle me-1"></i>{{ $message }}</div>@enderror
</div>
<div class="mb-3">
<label for="update_password_password_confirmation" class="form-label" style="font-size:11px;letter-spacing:.06em;text-transform:uppercase;color:#64748B;font-weight:600">Confirm Password</label>
<div class="input-group">
<input id="update_password_password_confirmation" name="password_confirmation" type="password" class="form-control" autocomplete="new-password" placeholder="••••••••">
<button class="btn btn-outline-secondary" type="button" onclick="togglePwd('update_password_password_confirmation',this)" aria-label="Toggle confirm password"><i class="bi bi-eye"></i></button>
</div>
@error('password_confirmation','updatePassword')<div class="text-danger mt-1" style="font-size:12px"><i class="bi bi-exclamation-circle me-1"></i>{{ $message }}</div>@enderror
</div>
<div class="d-flex align-items-center gap-3">
<button type="submit" class="btn btn-primary w-100"><i class="bi bi-check-lg me-1"></i> Save</button>
@if(session('status')==='password-updated')<span class="text-success" style="font-size:12px"><i class="bi bi-check-circle"></i> Saved.</span>@endif
</div>
</form>
</div>
</div>
<div class="card border-danger">
<div class="card-header bg-white border-danger d-flex align-items-center gap-2"><i class="bi bi-exclamation-triangle text-danger"></i><h5 class="mb-0" style="font-size:13px;letter-spacing:.06em;text-transform:uppercase;color:#DC2626">Delete Account</h5></div>
<div class="card-body">
<p class="text-muted mb-3" style="font-size:12px">Once your account is deleted, all of its resources and data will be permanently deleted. Before deleting your account, please download any data or information that you wish to retain.</p>
<button class="btn btn-danger w-100" data-bs-toggle="modal" data-bs-target="#confirmDeleteModal"><i class="bi bi-trash me-1"></i> Delete Account</button>
</div>
</div>
</div>
</div>
<div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-labelledby="confirmDeleteLabel" aria-hidden="true">
<div class="modal-dialog"><div class="modal-content">
<form method="post" action="{{ route('profile.destroy') }}">
@csrf
@method('delete')
<div class="modal-header"><h5 class="modal-title" id="confirmDeleteLabel" style="font-size:14px;font-weight:700">Are you sure you want to delete your account?</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
<div class="modal-body">
<p class="text-muted" style="font-size:13px">Once your account is deleted, all of its resources and data will be permanently deleted. Please enter your password to confirm you would like to permanently delete your account.</p>
<div class="mt-3">
<label for="delete_password" class="form-label" style="font-size:11px;letter-spacing:.06em;text-transform:uppercase;color:#64748B;font-weight:600">Password</label>
<div class="input-group">
<input id="delete_password" name="password" type="password" class="form-control" placeholder="Password">
<button class="btn btn-outline-secondary" type="button" onclick="togglePwd('delete_password',this)" aria-label="Toggle password"><i class="bi bi-eye"></i></button>
</div>
@error('password','userDeletion')<div class="text-danger mt-1" style="font-size:12px"><i class="bi bi-exclamation-circle me-1"></i>{{ $message }}</div>@enderror
</div>
</div>
<div class="modal-footer">
<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
<button type="submit" class="btn btn-danger"><i class="bi bi-trash me-1"></i> Delete Account</button>
</div>
</form>
</div></div>
</div>
@push("scripts")
<script>
function togglePwd(id,btn){const i=document.getElementById(id);if(!i)return;const icon=btn.querySelector('i');const isPwd=i.type==='password';i.type=isPwd?'text':'password';icon.className=isPwd?'bi bi-eye-slash':'bi bi-eye'}
@if($errors->userDeletion->isNotEmpty())
document.addEventListener('DOMContentLoaded',()=>{const m=new bootstrap.Modal(document.getElementById('confirmDeleteModal'));m.show()})
@endif
</script>
@endpush
@endsection
