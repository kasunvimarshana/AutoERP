<?php

declare(strict_types=1);

namespace Modules\Audit\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Audit\Models\AuditLog;
use Modules\Auth\Models\User;
use Modules\Tenant\Models\Tenant;
use Tests\TestCase;

/**
 * Audit Log API Test
 *
 * Tests the complete Audit Log REST API functionality
 */
class AuditLogApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        // Create tenant and user
        $this->tenant = Tenant::factory()->create();
        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        // Mock permission for testing
        $this->user->permissions = ['audit.view', 'audit.export'];
    }

    /** @test */
    public function it_lists_audit_logs_with_pagination(): void
    {
        // Create test audit logs
        AuditLog::factory()->count(20)->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/audit-logs?per_page=10');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'event',
                        'auditable_type',
                        'user_id',
                        'created_at',
                    ],
                ],
                'links',
                'meta',
            ])
            ->assertJsonCount(10, 'data');
    }

    /** @test */
    public function it_filters_audit_logs_by_event(): void
    {
        AuditLog::factory()->create([
            'tenant_id' => $this->tenant->id,
            'event' => 'created',
        ]);
        AuditLog::factory()->create([
            'tenant_id' => $this->tenant->id,
            'event' => 'updated',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/audit-logs?event=created');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.event', 'created');
    }

    /** @test */
    public function it_filters_audit_logs_by_date_range(): void
    {
        $oldLog = AuditLog::factory()->create([
            'tenant_id' => $this->tenant->id,
            'created_at' => now()->subDays(10),
        ]);

        $recentLog = AuditLog::factory()->create([
            'tenant_id' => $this->tenant->id,
            'created_at' => now()->subDay(),
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/audit-logs?from_date='.now()->subDays(2)->toDateString());

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }

    /** @test */
    public function it_shows_specific_audit_log(): void
    {
        $auditLog = AuditLog::factory()->create([
            'tenant_id' => $this->tenant->id,
            'event' => 'updated',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/v1/audit-logs/{$auditLog->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $auditLog->id)
            ->assertJsonPath('data.event', 'updated');
    }

    /** @test */
    public function it_returns_statistics(): void
    {
        AuditLog::factory()->count(5)->create([
            'tenant_id' => $this->tenant->id,
            'event' => 'created',
        ]);
        AuditLog::factory()->count(3)->create([
            'tenant_id' => $this->tenant->id,
            'event' => 'updated',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/audit-logs/statistics');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'total_logs',
                    'by_event',
                    'by_auditable_type',
                    'by_user',
                    'timeline',
                    'recent_activity',
                ],
            ])
            ->assertJsonPath('data.total_logs', 8);
    }

    /** @test */
    public function it_exports_audit_logs_as_csv(): void
    {
        AuditLog::factory()->count(5)->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->get('/api/v1/audit-logs/export?format=csv');

        $response->assertOk()
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    }

    /** @test */
    public function it_exports_audit_logs_as_json(): void
    {
        AuditLog::factory()->count(5)->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->get('/api/v1/audit-logs/export?format=json');

        $response->assertOk()
            ->assertHeader('Content-Type', 'application/json; charset=UTF-8');
    }

    /** @test */
    public function it_enforces_tenant_isolation(): void
    {
        $otherTenant = Tenant::factory()->create();
        $otherLog = AuditLog::factory()->create([
            'tenant_id' => $otherTenant->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/v1/audit-logs/{$otherLog->id}");

        $response->assertNotFound();
    }

    /** @test */
    public function it_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/audit-logs');

        $response->assertUnauthorized();
    }

    /** @test */
    public function it_validates_pagination_limits(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/audit-logs?per_page=200');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['per_page']);
    }

    /** @test */
    public function it_searches_in_json_fields(): void
    {
        AuditLog::factory()->create([
            'tenant_id' => $this->tenant->id,
            'new_values' => ['price' => '100.00', 'name' => 'Test Product'],
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/audit-logs?search=price');

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }
}
