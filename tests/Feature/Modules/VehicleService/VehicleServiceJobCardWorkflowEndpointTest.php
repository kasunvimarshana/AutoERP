<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\VehicleService;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class VehicleServiceJobCardWorkflowEndpointTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_job_card_aggregate_persists_real_workshop_lines_combo_components_and_labour_assignments(): void
    {
        [$tenantId, $organizationUnitId, $headers] = $this->authenticatedHeaders();

        $customerId = (int) DB::table('customers')->where('tenant_id', $tenantId)->where('customer_code', 'CUS-DEMO-001')->value('id');
        $vehicleId = (int) DB::table('vehicles')->where('tenant_id', $tenantId)->where('vehicle_code', 'VEH-DEMO-001')->value('id');
        $employeeId = $this->ensureActiveTechnician($tenantId, $organizationUnitId);
        $serviceTypeId = $this->ensureServiceType($tenantId, $organizationUnitId);

        $eachUomId = (int) DB::table('unit_of_measures')->where('tenant_id', $tenantId)->where('name', 'Each')->value('id');
        $hourUomId = (int) DB::table('unit_of_measures')->where('tenant_id', $tenantId)->where('name', 'Hour')->value('id');

        $filterItemId = $this->itemId($tenantId, 'ITM-FILTER-001');
        $serviceItemId = $this->itemId($tenantId, 'ITM-SERVICE-001');
        $labourItemId = $this->itemId($tenantId, 'ITM-LABOUR-001');
        $shopSupplyItemId = $this->itemId($tenantId, 'ITM-SHOPSUPPLY-001');
        $comboItemId = $this->itemId($tenantId, 'ITM-BUNDLE-001');

        $this->ensureComboLabourComponent($tenantId, $organizationUnitId, $comboItemId, $labourItemId, $hourUomId);

        $response = $this->withHeaders($headers)->postJson('/api/vehicle-service/job-cards/aggregate', [
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'job_card_number' => null,
            'service_type_id' => $serviceTypeId,
            'linked_customer_id' => $customerId,
            'service_customer_type' => 'customer',
            'service_customer_id' => $customerId,
            'billing_customer_type' => 'customer',
            'billing_customer_id' => $customerId,
            'payer_type' => 'customer',
            'payer_id' => $customerId,
            'vehicle_id' => $vehicleId,
            'assigned_to' => $employeeId,
            'start_datetime' => '2026-06-02 09:00:00',
            'promised_delivery_date_time' => '2026-06-02 17:00:00',
            'start_odometer' => 12000,
            'reported_issue' => 'Customer reports service due and brake noise.',
            'notes' => 'Internal workshop notes.',
            'status' => 'open',
            'lines' => [
                [
                    'metadata' => ['client_key' => 'product-line'],
                    'item_id' => $filterItemId,
                    'line_type' => 'inventory',
                    'quantity' => 1,
                    'uom_id' => $eachUomId,
                    'unit_price' => 1250,
                    'requires_stock_movement' => true,
                    'description' => 'Oil filter replacement.',
                ],
                [
                    'metadata' => ['client_key' => 'service-line'],
                    'item_id' => $serviceItemId,
                    'line_type' => 'service',
                    'quantity' => 1,
                    'uom_id' => $hourUomId,
                    'unit_price' => 2500,
                    'requires_stock_movement' => false,
                    'description' => 'General inspection service.',
                ],
                [
                    'metadata' => ['client_key' => 'combo-line'],
                    'item_id' => $comboItemId,
                    'line_type' => 'combo',
                    'quantity' => 1,
                    'uom_id' => $eachUomId,
                    'unit_price' => 5000,
                    'requires_stock_movement' => false,
                    'description' => 'Basic service package.',
                ],
            ],
            'labor_items' => [
                [
                    'metadata' => ['client_key' => 'labour-line'],
                    'item_id' => $labourItemId,
                    'quantity' => 2,
                    'uom_id' => $hourUomId,
                    'unit_price' => 1800,
                    'description' => 'Technician labour.',
                ],
            ],
            'non_inventory_items' => [
                [
                    'metadata' => ['client_key' => 'charge-line'],
                    'item_id' => $shopSupplyItemId,
                    'quantity' => 1,
                    'uom_id' => $eachUomId,
                    'unit_price' => 350,
                    'description' => 'Shop supplies.',
                ],
            ],
            'labor_assignments' => [
                [
                    'metadata' => ['client_key' => 'assignment-line'],
                    'labor_item_client_key' => 'labour-line',
                    'employee_id' => $employeeId,
                    'role' => 'lead',
                    'hours_worked' => 2,
                    'status' => 'assigned',
                    'notes' => 'Assign lead technician.',
                ],
            ],
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.tenant_id', $tenantId);

        $jobCardId = (int) $response->json('data.id');
        $jobCardNumber = (string) $response->json('data.job_card_number');

        self::assertNotSame('', $jobCardNumber);
        self::assertStringStartsWith('VSJC-', $jobCardNumber);

        $this->assertDatabaseHas('vehicle_service_job_cards', [
            'id' => $jobCardId,
            'tenant_id' => $tenantId,
            'job_card_number' => $jobCardNumber,
            'linked_customer_id' => $customerId,
            'vehicle_id' => $vehicleId,
        ]);

        $this->assertDatabaseHas('vehicle_service_job_card_lines', [
            'tenant_id' => $tenantId,
            'job_card_id' => $jobCardId,
            'item_id' => $filterItemId,
            'line_type' => 'inventory',
        ]);

        $this->assertDatabaseHas('vehicle_service_job_card_lines', [
            'tenant_id' => $tenantId,
            'job_card_id' => $jobCardId,
            'item_id' => $comboItemId,
            'line_type' => 'combo',
        ]);

        $this->assertDatabaseHas('vehicle_service_labor_items', [
            'tenant_id' => $tenantId,
            'job_card_id' => $jobCardId,
            'item_id' => $labourItemId,
            'is_combo_component' => false,
        ]);

        $this->assertDatabaseHas('vehicle_service_labor_items', [
            'tenant_id' => $tenantId,
            'job_card_id' => $jobCardId,
            'item_id' => $labourItemId,
            'is_combo_component' => true,
        ]);

        $this->assertDatabaseHas('vehicle_service_non_inventory_items', [
            'tenant_id' => $tenantId,
            'job_card_id' => $jobCardId,
            'name' => 'ITM-SHOPSUPPLY-001 - Workshop Consumable Reference',
        ]);

        $laborItemId = (int) DB::table('vehicle_service_labor_items')
            ->where('tenant_id', $tenantId)
            ->where('job_card_id', $jobCardId)
            ->where('is_combo_component', false)
            ->value('id');

        $this->assertDatabaseHas('vehicle_service_labor_assignments', [
            'tenant_id' => $tenantId,
            'job_card_id' => $jobCardId,
            'labor_item_id' => $laborItemId,
            'employee_id' => $employeeId,
            'role' => 'lead',
            'status' => 'assigned',
        ]);

        $this->withHeaders($headers)
            ->getJson('/api/vehicle-service/vehicle-service-labor-items?tenant_id='.$tenantId.'&job_card_id='.$jobCardId)
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonFragment(['item_id' => $labourItemId]);

        $this->withHeaders($headers)
            ->getJson('/api/vehicle-service/vehicle-service-labor-assignments?tenant_id='.$tenantId.'&job_card_id='.$jobCardId)
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.labor_item_id', $laborItemId)
            ->assertJsonPath('data.0.employee.id', $employeeId);
    }

    /**
     * @return array{0:int,1:int,2:array<string,string>}
     */
    private function authenticatedHeaders(): array
    {
        $tenantId = (int) DB::table('tenants')->where('code', 'AUTOERP')->value('id');
        $organizationUnitId = (int) DB::table('organization_units')
            ->where('tenant_id', $tenantId)
            ->where('code', 'MAIN')
            ->value('id');

        $loginResponse = $this->postJson('/api/auth/login', [
            'login_identifier' => 'admin@example.com',
            'password' => 'password',
            'provider_key' => 'internal',
            'tenant_id' => $tenantId,
        ]);

        $loginResponse->assertOk();

        return [
            $tenantId,
            $organizationUnitId,
            [
                'Authorization' => 'Bearer '.(string) $loginResponse->json('data.tokens.access_token'),
                'X-Organization-Unit-ID' => (string) $organizationUnitId,
                'X-Tenant-ID' => (string) $tenantId,
            ],
        ];
    }

    private function ensureActiveTechnician(int $tenantId, int $organizationUnitId): int
    {
        DB::table('employees')->updateOrInsert(
            ['tenant_id' => $tenantId, 'employee_code' => 'EMP-SVC-001'],
            [
                'row_version' => 1,
                'organization_unit_id' => $organizationUnitId,
                'first_name' => 'Service',
                'last_name' => 'Technician',
                'display_name' => 'Service Technician',
                'full_name' => 'Service Technician',
                'email' => 'technician@example.test',
                'employment_status' => 'active',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );

        return (int) DB::table('employees')->where('tenant_id', $tenantId)->where('employee_code', 'EMP-SVC-001')->value('id');
    }

    private function ensureServiceType(int $tenantId, int $organizationUnitId): int
    {
        DB::table('vehicle_service_types')->updateOrInsert(
            ['tenant_id' => $tenantId, 'name' => 'General Service'],
            [
                'row_version' => 1,
                'organization_unit_id' => $organizationUnitId,
                'code' => 'GENERAL_SERVICE',
                'path' => 'general-service',
                'description' => 'General workshop service.',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );

        return (int) DB::table('vehicle_service_types')->where('tenant_id', $tenantId)->where('name', 'General Service')->value('id');
    }

    private function ensureComboLabourComponent(int $tenantId, int $organizationUnitId, int $comboItemId, int $labourItemId, int $hourUomId): void
    {
        DB::table('combo_items')->updateOrInsert(
            [
                'tenant_id' => $tenantId,
                'combo_item_id' => $comboItemId,
                'component_item_id' => $labourItemId,
            ],
            [
                'row_version' => 1,
                'organization_unit_id' => $organizationUnitId,
                'quantity' => 1,
                'uom_id' => $hourUomId,
                'sort_order' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );
    }

    private function itemId(int $tenantId, string $sku): int
    {
        return (int) DB::table('items')
            ->where('tenant_id', $tenantId)
            ->where('sku', $sku)
            ->value('id');
    }
}
