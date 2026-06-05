<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Inventory\Application\Services\StockReceivingService;
use Tests\TestCase;

final class VehicleServiceFlowTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<string, string> */
    private array $headers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $login = $this->postJson('/api/auth/login', [
            'tenant_id' => 1,
            'organization_unit_id' => 1,
            'provider_key' => 'internal',
            'login_identifier' => 'admin@example.com',
            'password' => 'password',
            'device_name' => 'Vehicle service test',
        ])->assertOk();

        $this->headers = [
            'Authorization' => 'Bearer '.$login->json('data.tokens.access_token'),
            'X-Tenant-ID' => '1',
            'X-Organization-Unit-ID' => '1',
        ];
    }

    public function test_vehicle_service_job_consumes_inventory_invoices_and_accepts_multiple_payments(): void
    {
        $uomId = (int) DB::table('unit_of_measures')->where('tenant_id', 1)->where('uom_code', 'PCS')->value('id');
        $warehouseId = (int) DB::table('warehouses')->where('tenant_id', 1)->where('code', 'MAIN')->value('id');
        $paymentMethodId = (int) DB::table('payment_methods')->where('tenant_id', 1)->where('code', 'CASH')->value('id');
        $customerId = $this->customerId();
        $vehicleId = $this->vehicleId();
        $partId = $this->itemId($uomId, 'VS-PART-100', 'Brake Pad', true, false, 100, 150);
        $laborItemId = $this->itemId($uomId, 'VS-LAB-100', 'Brake Labor', false, true, 0, 200);

        app(StockReceivingService::class)->receive([
            'tenant_id' => 1,
            'organization_unit_id' => 1,
            'source_type' => 'opening_stock',
            'source_id' => 100,
            'warehouse_id' => $warehouseId,
            'lines' => [[
                'source_line_id' => 1,
                'item_id' => $partId,
                'uom_id' => $uomId,
                'quantity' => 5,
                'unit_cost' => 100,
            ]],
        ]);

        $serviceType = $this->withHeaders($this->headers)
            ->postJson('/api/vehicle-service/service-types', [
                'name' => 'Brake Service',
                'code' => 'BRAKE',
                'standard_hours' => 1,
            ])
            ->assertCreated()
            ->assertJsonPath('data.name', 'Brake Service');

        $job = $this->withHeaders($this->headers)
            ->postJson('/api/vehicle-service/job-cards', [
                'job_card_number' => 'JOB-FLOW-100',
                'linked_customer_id' => $customerId,
                'vehicle_id' => $vehicleId,
                'service_type_id' => (int) $serviceType->json('data.id'),
                'warehouse_id' => $warehouseId,
                'priority' => 'high',
                'reported_issue' => 'Brake noise',
                'start_odometer' => 12000,
                'parts' => [[
                    'item_id' => $partId,
                    'uom_id' => $uomId,
                    'warehouse_id' => $warehouseId,
                    'quantity' => 2,
                    'unit_price' => 150,
                    'unit_cost' => 100,
                    'discount_type' => 'fixed',
                    'discount_value' => 10,
                    'tax_amount' => 30,
                ]],
                'labor_items' => [[
                    'item_id' => $laborItemId,
                    'uom_id' => $uomId,
                    'quantity' => 1,
                    'unit_price' => 200,
                    'tax_amount' => 20,
                ]],
                'non_inventory_items' => [[
                    'name' => 'Cleaning material',
                    'uom_id' => $uomId,
                    'quantity' => 1,
                    'unit_price' => 50,
                    'discount_type' => 'fixed',
                    'discount_value' => 5,
                    'tax_amount' => 5,
                ]],
                'header_discount_type' => 'fixed',
                'header_discount_value' => 20,
                'header_tax_amount' => 10,
                'header_charge_amount' => 5,
                'header_adjustment_amount' => 2,
                'header_adjustment_effect' => 'deduct',
            ])
            ->assertCreated()
            ->assertJsonPath('data.status', 'open')
            ->assertJsonPath('data.discount_total', '35.0000')
            ->assertJsonPath('data.tax_total', '65.0000')
            ->assertJsonPath('data.grand_total', '583.0000');

        $jobId = (int) $job->json('data.id');
        $this->withHeaders($this->headers)
            ->postJson("/api/vehicle-service/job-cards/$jobId/complete")
            ->assertOk()
            ->assertJsonPath('data.status', 'completed')
            ->assertJsonPath('data.inventory_status', 'consumed');

        $this->withHeaders($this->headers)
            ->postJson("/api/vehicle-service/job-cards/$jobId/complete")
            ->assertOk()
            ->assertJsonPath('data.status', 'completed');

        $this->assertDatabaseHas('stock_levels', [
            'tenant_id' => 1,
            'item_id' => $partId,
            'warehouse_id' => $warehouseId,
            'quantity_on_hand' => 3,
        ]);
        $this->assertDatabaseCount('vehicle_service_job_inventory_links', 1);

        $invoice = $this->withHeaders($this->headers)
            ->postJson("/api/vehicle-service/job-cards/$jobId/invoice")
            ->assertOk()
            ->assertJsonPath('data.document_type', 'service_invoice')
            ->assertJsonPath('data.ledger_direction', 'receivable')
            ->assertJsonPath('data.grand_total', '583.0000')
            ->assertJsonPath('data.status', 'issued');
        $invoiceId = (int) $invoice->json('data.id');

        $duplicate = $this->withHeaders($this->headers)
            ->postJson("/api/vehicle-service/job-cards/$jobId/invoice")
            ->assertOk();
        $this->assertSame($invoiceId, (int) $duplicate->json('data.id'));
        $this->assertDatabaseCount('vehicle_service_job_invoice_links', 1);
        $this->assertDatabaseHas('journal_entries', ['tenant_id' => 1, 'source_module' => 'invoice', 'reference_id' => $invoiceId]);

        foreach ([200, 383] as $amount) {
            $this->withHeaders($this->headers)
                ->postJson('/api/payment/payments', [
                    'party_type' => 'customer',
                    'party_id' => $customerId,
                    'payment_date' => '2026-06-06',
                    'amount' => $amount,
                    'direction' => 'inbound',
                    'payment_method_id' => $paymentMethodId,
                    'allocations' => [[
                        'invoice_id' => $invoiceId,
                        'allocated_amount' => $amount,
                    ]],
                ])
                ->assertCreated();
        }

        $this->withHeaders($this->headers)
            ->getJson("/api/vehicle-service/job-cards/$jobId")
            ->assertOk()
            ->assertJsonPath('data.status', 'paid')
            ->assertJsonPath('data.payment_status', 'paid')
            ->assertJsonPath('data.paid_amount', '583.0000')
            ->assertJsonPath('data.balance', '0.0000');

        $this->assertDatabaseCount('payment_allocations', 2);
        $this->assertDatabaseCount('vehicle_service_job_payment_links', 2);
        $this->assertDatabaseCount('vehicle_service_job_status_histories', 4);
    }

    private function customerId(): int
    {
        return (int) DB::table('customers')->insertGetId([
            'tenant_id' => 1,
            'organization_unit_id' => 1,
            'customer_code' => 'VS-CUST-100',
            'customer_name' => 'Vehicle Service Customer',
            'customer_type' => 'individual',
            'credit_limit' => 5000,
            'payment_terms_days' => 0,
            'status' => 'active',
            'is_active' => true,
            'row_version' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function vehicleId(): int
    {
        return (int) DB::table('vehicles')->insertGetId([
            'tenant_id' => 1,
            'organization_unit_id' => 1,
            'vehicle_code' => 'VS-VEH-100',
            'registration_number' => 'VS-1000',
            'make' => 'Toyota',
            'model' => 'Corolla',
            'status' => 'active',
            'row_version' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function itemId(int $uomId, string $code, string $name, bool $stock, bool $service, float $cost, float $price): int
    {
        return (int) DB::table('items')->insertGetId([
            'tenant_id' => 1,
            'organization_unit_id' => 1,
            'item_code' => $code,
            'name' => $name,
            'base_uom_id' => $uomId,
            'track_inventory' => $stock,
            'is_stock_item' => $stock,
            'is_service_item' => $service,
            'cost_price' => $cost,
            'sales_price' => $price,
            'reorder_level' => 0,
            'reorder_quantity' => 0,
            'status' => 'active',
            'row_version' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
