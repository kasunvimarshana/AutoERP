<?php

namespace Modules\Core\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Modules\Core\Models\AuditLog;
use Modules\Core\Models\Tenant;
use Tests\TestCase;

class AuditLogApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->tenant = Tenant::create([
            'name' => 'Test Tenant',
            'domain' => 'test.example.com',
        ]);
    }

    public function test_can_list_audit_logs()
    {
        Sanctum::actingAs($this->user);

        AuditLog::create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'auditable_type' => Tenant::class,
            'auditable_id' => $this->tenant->id,
            'event' => 'created',
            'new_values' => ['name' => 'Test'],
        ]);

        $response = $this->getJson('/api/audit-logs');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'data' => [
                        '*' => ['id', 'event', 'auditable_type', 'auditable_id'],
                    ],
                ],
            ]);
    }

    public function test_can_show_audit_log()
    {
        Sanctum::actingAs($this->user);

        $log = AuditLog::create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'auditable_type' => Tenant::class,
            'auditable_id' => $this->tenant->id,
            'event' => 'created',
            'new_values' => ['name' => 'Test'],
        ]);

        $response = $this->getJson("/api/audit-logs/{$log->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $log->id,
                    'event' => 'created',
                ],
            ]);
    }

    public function test_can_filter_audit_logs_by_event()
    {
        Sanctum::actingAs($this->user);

        AuditLog::create([
            'tenant_id' => $this->tenant->id,
            'event' => 'created',
            'auditable_type' => Tenant::class,
            'auditable_id' => $this->tenant->id,
        ]);

        AuditLog::create([
            'tenant_id' => $this->tenant->id,
            'event' => 'updated',
            'auditable_type' => Tenant::class,
            'auditable_id' => $this->tenant->id,
        ]);

        $response = $this->getJson('/api/audit-logs?event=created');

        $response->assertStatus(200);

        $data = $response->json('data.data');
        $this->assertCount(1, $data);
        $this->assertEquals('created', $data[0]['event']);
    }
}
