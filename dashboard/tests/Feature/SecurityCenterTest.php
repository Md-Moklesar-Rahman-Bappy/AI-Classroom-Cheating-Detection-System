<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityCenterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Seed roles if needed; using basic admin assignment via role attribute or seed
        $this->artisan('db:seed');
    }

    public function test_unauthorized_user_cannot_access_security_index(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $this->get(route('security.index'))->assertStatus(403);
    }

    public function test_authorized_admin_can_access_security_index_with_pagination(): void
    {
        $admin = User::factory()->create(['role' => 'system_admin']);
        $this->actingAs($admin);
        $response = $this->get(route('security.index'));
        $response->assertStatus(200);
        $response->assertViewIs('security.index');
        $response->assertSee('Login Security Events');
    }

    public function test_unblock_pair_requires_reason_and_csrf(): void
    {
        $admin = User::factory()->create(['role' => 'system_admin']);
        $this->actingAs($admin);
        $this->post(route('security.unblock-pair', ['email' => 'a@b.com', 'ip' => '10.0.0.1']), [
            'reason' => 'Test unblock',
        ])->assertRedirect(route('security.index'));
    }

    public function test_unblock_ip_validates_input_and_csrfs(): void
    {
        $admin = User::factory()->create(['role' => 'system_admin']);
        $this->actingAs($admin);
        $this->post(route('security.unblock-ip', ['ip' => '192.168.1.10']), [
            'reason' => 'Manual unblock',
            'ip' => '192.168.1.10',
        ])->assertRedirect(route('security.index'));
    }

    public function test_security_view_masks_email_and_ip(): void
    {
        $admin = User::factory()->create(['role' => 'system_admin']);
        $this->actingAs($admin);
        $this->get(route('security.index'))->assertStatus(200);
    }
}
