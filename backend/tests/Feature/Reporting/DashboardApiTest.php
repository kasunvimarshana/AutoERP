<?php

namespace Tests\Feature\Reporting;

use App\Models\Tenant;
use App\Models\User;
use App\Modules\Reporting\Models\Dashboard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DashboardApiTest extends TestCase
{
    use RefreshDatabase;

    private string $baseUri = '/api/v1/reports/dashboards';

    private Tenant $tenant;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();
        $this->user = User::factory()->create(['tenant_id' => $this->tenant->id]);
        Sanctum::actingAs($this->user);
    }

    public function test_can_create_dashboard(): void
    {
        $data = [
            'name' => 'My Dashboard',
            'description' => 'A test dashboard',
            'layout_config' => [
                'columns' => 12,
                'rows' => 'auto',
            ],
            'is_default' => false,
        ];

        $response = $this->postJson($this->baseUri, $data);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'name',
                    'description',
                    'layout_config',
                ],
            ]);

        $this->assertDatabaseHas('dashboards', [
            'name' => 'My Dashboard',
            'user_id' => $this->user->id,
            'tenant_id' => $this->tenant->id,
        ]);
    }

    public function test_can_list_dashboards(): void
    {
        Dashboard::factory()->count(3)->create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
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
                        'description',
                    ],
                ],
            ])
            ->assertJsonCount(3, 'data');
    }

    public function test_can_get_single_dashboard(): void
    {
        $dashboard = Dashboard::factory()->create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->getJson("{$this->baseUri}/{$dashboard->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'name',
                    'widgets',
                ],
            ]);
    }

    public function test_can_set_dashboard_as_default(): void
    {
        $dashboard = Dashboard::factory()->create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'is_default' => false,
        ]);

        $response = $this->postJson("{$this->baseUri}/{$dashboard->id}/set-default");

        $response->assertStatus(200);

        $this->assertDatabaseHas('dashboards', [
            'id' => $dashboard->id,
            'is_default' => true,
        ]);
    }

    public function test_can_get_default_dashboard(): void
    {
        Dashboard::factory()->create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'is_default' => false,
        ]);

        $defaultDashboard = Dashboard::factory()->create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'is_default' => true,
        ]);

        $response = $this->getJson("{$this->baseUri}/default");

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $defaultDashboard->id,
                    'is_default' => true,
                ],
            ]);
    }
}
