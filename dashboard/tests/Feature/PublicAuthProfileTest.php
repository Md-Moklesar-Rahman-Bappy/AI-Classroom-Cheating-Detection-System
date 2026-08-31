<?php

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('GET / serves project landing without Laravel default welcome', function () {
    $response = $this->get('/');

    $response->assertOk();
    $response->assertSee('AI Classroom Cheating Detection System');
    $response->assertSee('Real-Time Exam Surveillance');
    $response->assertDontSee('Let’s get started');
    $response->assertDontSee('Laracasts');
    $response->assertSee('AI Classroom');
    $response->assertSee('Surveillance Platform');
});

test('GET / contains surveillance mock and responsible AI notice', function () {
    $response = $this->get('/');

    $response->assertSee('person 0.92');
    $response->assertSee('AI-generated alerts indicate observable events');
});

test('GET /login uses project guest layout with password toggle', function () {
    $response = $this->get('/login');

    $response->assertOk();
    $response->assertSee('AI Classroom');
    $response->assertSee('Surveillance Platform');
    $response->assertSee('Welcome back');
    $response->assertSee('togglePassword', false);
    $response->assertSee('Show password', false);
    $response->assertSee('type="password"', false);
    $response->assertSee('aria-label="Show password"', false);
    $response->assertSee('csrf-token', false);
});

test('GET /register uses project guest layout with two password toggles', function () {
    $response = $this->get('/register');

    $response->assertOk();
    $response->assertSee('AI Classroom');
    $response->assertSee('Create account');
    $response->assertSee('togglePassword', false);
    $response->assertSee('password_confirmation', false);
    $response->assertSee('strengthBar', false);
});

test('GET /forgot-password uses project guest layout', function () {
    $response = $this->get('/forgot-password');

    $response->assertOk();
    $response->assertSee('AI Classroom');
    $response->assertSee('Forgot password');
});

test('GET /reset-password/{token} uses project guest layout with toggles', function () {
    $response = $this->get('/reset-password/test-token-123');

    $response->assertOk();
    $response->assertSee('AI Classroom');
    $response->assertSee('Reset password');
    $response->assertSee('togglePassword', false);
    $response->assertSee('password_confirmation', false);
});

test('GET /verify-email requires authentication and redirects to login', function () {
    $response = $this->get('/verify-email');

    $response->assertRedirect('/login');
});

test('GET /verify-email renders correctly for authenticated user', function () {
    $user = User::factory()->unverified()->create();
    $response = $this->actingAs($user)->get('/verify-email');

    $response->assertOk();
    $response->assertSee('Verify email');
    $response->assertSee('AI Classroom');
});

test('GET /profile uses dashboard sidebar shell and shows human-readable role', function () {
    $user = User::factory()->create();
    $role = Role::firstOrCreate(['name' => 'system_admin'], ['description' => 'System Administrator', 'display_name' => 'System Administrator']);
    $user->roles()->sync([$role->id]);

    $response = $this->actingAs($user)->get('/profile');

    $response->assertOk();
    $response->assertSee('Profile');
    $response->assertSee('System Administrator');
    $response->assertSee('sidebar', false);
    $response->assertSee('AI Classroom', false);
    $response->assertDontSee('x-app-layout', false);
});

test('GET /profile shows accessible password toggles for current new and confirm fields', function () {
    $user = User::factory()->create();
    $response = $this->actingAs($user)->get('/profile');

    $response->assertOk();
    $response->assertSee('Current Password', false);
    $response->assertSee('New Password', false);
    $response->assertSee('Confirm Password', false);
    $response->assertSee('togglePwd', false);
    $response->assertSee('update_password_current_password', false);
    $response->assertSee('update_password_password', false);
    $response->assertSee('update_password_password_confirmation', false);
    $response->assertSee('aria-label="Show password"', false);
});

test('DELETE /profile blocks last active system_admin', function () {
    $role = Role::firstOrCreate(['name' => 'system_admin'], ['description' => 'System Administrator']);
    $admin = User::factory()->create(['password' => Hash::make('password')]);
    $admin->roles()->sync([$role->id]);

    User::whereHas('roles', fn ($q) => $q->where('name', 'system_admin'))->where('id', '!=', $admin->id)->delete();

    $count = User::whereHas('roles', fn ($q) => $q->where('name', 'system_admin'))->count();
    expect($count)->toBe(1);

    $response = $this->actingAs($admin)->delete('/profile', ['password' => 'password']);

    $response->assertSessionHasErrorsIn('userDeletion', 'password');
    expect(User::find($admin->id))->not->toBeNull();
});

test('DELETE /users blocks last active system_admin via UserController', function () {
    $role = Role::firstOrCreate(['name' => 'system_admin'], ['description' => 'System Administrator']);
    $admin = User::factory()->create();
    $admin->roles()->sync([$role->id]);
    $other = User::factory()->create();
    $other->roles()->sync([$role->id]);
    $other->delete();

    $count = User::whereHas('roles', fn ($q) => $q->where('name', 'system_admin'))->count();
    expect($count)->toBe(1);

    $response = $this->actingAs($admin)->delete("/users/{$admin->id}");

    $response->assertSessionHasErrors('user');
    expect(User::find($admin->id))->not->toBeNull();
});

test('password show hide button type is button not submit and has aria pressed', function () {
    $response = $this->get('/login');
    $response->assertSee('type="button" aria-label="Show password" aria-pressed="false"', false);
});
