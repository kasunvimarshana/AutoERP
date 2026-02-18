<?php

namespace Tests\Feature\Api\Manufacturing;

use App\Modules\Manufacturing\Enums\WorkOrderStatus;
use App\Modules\Manufacturing\Models\ProductionOrder;
use App\Modules\Manufacturing\Models\WorkOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\FeatureTestCase;

class WorkOrderApiTest extends FeatureTestCase
{
    use RefreshDatabase;

    protected string $baseUrl = '/api/v1/manufacturing/work-orders';

    public function test_can_list_work_orders(): void
    {
        $this->actingAsUser();

        WorkOrder::factory()
            ->forTenant($this->tenant)
            ->count(3)
            ->create();

        $response = $this->getJson($this->baseUrl);

        $this->assertJsonSuccess($response)
            ->assertJsonStructure([
                'data' => [
                    'data' => [
                        '*' => ['id', 'work_order_number', 'production_order_id', 'status'],
                    ],
                ],
            ]);
    }

    public function test_can_create_work_order(): void
    {
        $this->actingAsUser();

        $po = ProductionOrder::factory()
            ->forTenant($this->tenant)
            ->create();

        $data = [
            'work_order_number' => 'WO-TEST-001',
            'production_order_id' => $po->id,
            'workstation' => 'Assembly Line 1',
            'description' => 'Test work order',
            'scheduled_start' => now()->toDateTimeString(),
            'scheduled_end' => now()->addHours(8)->toDateTimeString(),
            'status' => 'pending',
        ];

        $response = $this->postJson($this->baseUrl, $data);

        $this->assertJsonSuccess($response, 201)
            ->assertJsonFragment(['work_order_number' => 'WO-TEST-001']);
    }

    public function test_can_show_work_order(): void
    {
        $this->actingAsUser();

        $wo = WorkOrder::factory()
            ->forTenant($this->tenant)
            ->create();

        $response = $this->getJson("{$this->baseUrl}/{$wo->id}");

        $this->assertJsonSuccess($response)
            ->assertJsonFragment(['id' => $wo->id]);
    }

    public function test_can_start_work_order(): void
    {
        $this->actingAsUser();

        $wo = WorkOrder::factory()
            ->forTenant($this->tenant)
            ->pending()
            ->create();

        $response = $this->postJson("{$this->baseUrl}/{$wo->id}/start");

        $this->assertJsonSuccess($response)
            ->assertJsonFragment(['status' => WorkOrderStatus::IN_PROGRESS->value]);
    }

    public function test_can_complete_work_order(): void
    {
        $this->actingAsUser();

        $wo = WorkOrder::factory()
            ->forTenant($this->tenant)
            ->inProgress()
            ->create();

        $data = [
            'actual_end' => now()->toDateTimeString(),
            'notes' => 'Work completed successfully',
        ];

        $response = $this->postJson("{$this->baseUrl}/{$wo->id}/complete", $data);

        $this->assertJsonSuccess($response)
            ->assertJsonFragment(['status' => WorkOrderStatus::COMPLETED->value]);
    }

    public function test_can_cancel_work_order(): void
    {
        $this->actingAsUser();

        $wo = WorkOrder::factory()
            ->forTenant($this->tenant)
            ->pending()
            ->create();

        $response = $this->postJson("{$this->baseUrl}/{$wo->id}/cancel");

        $this->assertJsonSuccess($response)
            ->assertJsonFragment(['status' => WorkOrderStatus::CANCELLED->value]);
    }

    public function test_can_get_my_work_orders(): void
    {
        $user = $this->actingAsUser();

        WorkOrder::factory()
            ->forTenant($this->tenant)
            ->create(['assigned_to' => $user->id]);

        $response = $this->getJson("{$this->baseUrl}/my-work-orders");

        $this->assertJsonSuccess($response);
    }

    public function test_can_get_pending_work_orders(): void
    {
        $this->actingAsUser();

        WorkOrder::factory()
            ->forTenant($this->tenant)
            ->pending()
            ->count(2)
            ->create();

        $response = $this->getJson("{$this->baseUrl}/pending");

        $this->assertJsonSuccess($response);
    }
}
