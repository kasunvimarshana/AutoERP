<?php

namespace Tests\Feature\Reporting;

use App\Models\Tenant;
use App\Models\User;
use App\Modules\Reporting\Enums\ReportType;
use App\Modules\Reporting\Models\Report;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ReportApiTest extends TestCase
{
    use RefreshDatabase;

    private string $baseUri = '/api/v1/reports';

    private Tenant $tenant;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();
        $this->user = User::factory()->create(['tenant_id' => $this->tenant->id]);
        Sanctum::actingAs($this->user);
    }

    public function test_can_create_report(): void
    {
        $data = [
            'name' => 'Test Report',
            'description' => 'A test report',
            'report_type' => ReportType::TABLE->value,
            'module' => 'inventory',
            'query_config' => [
                'pre_built' => true,
                'report_name' => 'stock_level',
            ],
            'is_public' => true,
        ];

        $response = $this->postJson($this->baseUri, $data);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'name',
                    'report_type',
                    'module',
                    'query_config',
                ],
            ]);

        $this->assertDatabaseHas('reports', [
            'name' => 'Test Report',
            'module' => 'inventory',
            'tenant_id' => $this->tenant->id,
        ]);
    }

    public function test_can_list_reports(): void
    {
        Report::factory()->count(3)->create([
            'tenant_id' => $this->tenant->id,
            'created_by_id' => $this->user->id,
        ]);

        $response = $this->getJson($this->baseUri);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'report_type',
                        'module',
                    ],
                ],
            ])
            ->assertJsonCount(3, 'data');
    }

    public function test_can_get_single_report(): void
    {
        $report = Report::factory()->create([
            'tenant_id' => $this->tenant->id,
            'created_by_id' => $this->user->id,
        ]);

        $response = $this->getJson("{$this->baseUri}/{$report->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'name',
                    'report_type',
                    'module',
                    'query_config',
                ],
            ])
            ->assertJson([
                'data' => [
                    'id' => $report->id,
                    'name' => $report->name,
                ],
            ]);
    }

    public function test_can_update_report(): void
    {
        $report = Report::factory()->create([
            'tenant_id' => $this->tenant->id,
            'created_by_id' => $this->user->id,
        ]);

        $updateData = [
            'name' => 'Updated Report Name',
            'description' => 'Updated description',
        ];

        $response = $this->putJson("{$this->baseUri}/{$report->id}", $updateData);

        $response->assertStatus(200)
            ->assertJsonFragment([
                'name' => 'Updated Report Name',
            ]);

        $this->assertDatabaseHas('reports', [
            'id' => $report->id,
            'name' => 'Updated Report Name',
        ]);
    }

    public function test_can_delete_report(): void
    {
        $report = Report::factory()->create([
            'tenant_id' => $this->tenant->id,
            'created_by_id' => $this->user->id,
        ]);

        $response = $this->deleteJson("{$this->baseUri}/{$report->id}");

        $response->assertStatus(200);

        $this->assertSoftDeleted('reports', [
            'id' => $report->id,
        ]);
    }

    public function test_can_get_reports_by_module(): void
    {
        Report::factory()->count(2)->create([
            'tenant_id' => $this->tenant->id,
            'created_by_id' => $this->user->id,
            'module' => 'inventory',
        ]);

        Report::factory()->create([
            'tenant_id' => $this->tenant->id,
            'created_by_id' => $this->user->id,
            'module' => 'sales',
        ]);

        $response = $this->getJson("{$this->baseUri}/by-module?module=inventory");

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }
}
