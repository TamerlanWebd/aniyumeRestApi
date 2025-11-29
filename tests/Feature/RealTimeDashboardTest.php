<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RealTimeDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_dashboard_stats()
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)
            ->getJson('/api/dashboard/stats');

        $response->assertStatus(200)
            ->assertJsonStructure(['anime_count', 'user_count']);
    }

    public function test_admin_can_access_audit_logs()
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)
            ->getJson('/api/audit-logs');

        $response->assertStatus(200)
            ->assertJsonStructure(['data']);
    }

    public function test_audit_log_is_created_on_model_change()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        // Create a user to trigger observer
        $user = User::factory()->create();

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'create',
            'model_type' => User::class,
            'model_id' => $user->id,
        ]);
    }
}
