<?php
namespace Tests\Feature;
use App\Models\DetectionEvent;
use App\Models\User;
class BulkDeleteReproTest extends \Tests\TestCase
{
    public function test_bulk_delete_reaches_controller_and_redirects()
    {
        $admin = User::factory()->create();
        $admin->update(['role'=>'system_admin']);
        $ids = DetectionEvent::limit(2)->pluck('id')->toArray();
        if (count($ids) < 2) $this->markTestSkipped('Need 2 existing DetectionEvents');
        $resp = $this->actingAs($admin)->post(route('detection-events.bulk-delete'), ['ids'=>$ids]);
        $this->assertFalse($resp->isNotFound(), 'Bulk delete must not return 404');
        $resp->assertRedirect();
    }
}
