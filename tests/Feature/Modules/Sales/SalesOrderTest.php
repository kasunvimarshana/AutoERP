<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Sales;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesOrderTest extends TestCase
{
    use RefreshDatabase;

    private int $tenantId;

    private int $productId;

    private array $defaultLine;

    private array $defaultOrder;

    protected function setUp(): void
    {
        parent::setUp();

        $tenantResponse = $this->postJson('/api/v1/tenants', [
            'name' => 'Sales Test Tenant',
            'slug' => 'sales-test-tenant',
        ]);
        $this->tenantId = $tenantResponse->json('data.id');

        $productResponse = $this->postJson('/api/v1/products', [
            'tenant_id' => $this->tenantId,
            'sku' => 'PROD-001',
            'name' => 'Test Product',
            'type' => 'stockable',
            'uom' => 'pcs',
            'costing_method' => 'fifo',
            'cost_price' => '10.00',
            'sale_price' => '20.00',
        ]);
        $this->productId = $productResponse->json('data.id');

        $this->defaultLine = [
            'product_id' => $this->productId,
            'description' => 'Test line',
            'quantity' => '2',
            'unit_price' => '20.00',
            'tax_rate' => '10',
            'discount_rate' => '0',
        ];

        $this->defaultOrder = [
            'tenant_id' => $this->tenantId,
            'customer_name' => 'Acme Corp',
            'customer_email' => 'acme@example.com',
            'order_date' => '2026-01-15',
            'currency' => 'LKR',
            'lines' => [$this->defaultLine],
        ];
    }

    public function test_can_create_sales_order(): void
    {
        $response = $this->postJson('/api/v1/sales/orders', $this->defaultOrder);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id', 'tenant_id', 'order_number', 'customer_name', 'customer_email',
                    'status', 'order_date', 'currency',
                    'subtotal', 'tax_amount', 'discount_amount', 'total_amount',
                    'lines' => [['id', 'product_id', 'quantity', 'unit_price', 'line_total']],
                ],
                'errors',
            ])
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'draft')
            ->assertJsonPath('data.customer_name', 'Acme Corp')
            ->assertJsonPath('data.tenant_id', $this->tenantId);
    }

    public function test_new_order_has_draft_status(): void
    {
        $response = $this->postJson('/api/v1/sales/orders', $this->defaultOrder);

        $response->assertStatus(201)
            ->assertJsonPath('data.status', 'draft');
    }

    public function test_order_number_is_generated_automatically(): void
    {
        $response = $this->postJson('/api/v1/sales/orders', $this->defaultOrder);

        $response->assertStatus(201);
        $orderNumber = $response->json('data.order_number');
        $this->assertStringStartsWith('SO-', $orderNumber);
    }

    public function test_line_totals_are_calculated_with_bcmath_precision(): void
    {
        $response = $this->postJson('/api/v1/sales/orders', $this->defaultOrder);

        $response->assertStatus(201);

        $data = $response->json('data');

        // quantity=2, unit_price=20.00, tax_rate=10%, discount_rate=0%
        // gross = 40.00, discount = 0.00, after_disc = 40.00
        // tax_amt = 40.00 * 0.10 = 4.00, line_total = 44.00
        $line = $data['lines'][0];
        $this->assertEquals('44.0000', $line['line_total']);
        // subtotal = 40.00 (sum of after_disc), tax = 4.00, total = 44.00
        $this->assertEquals('40.0000', $data['subtotal']);
        $this->assertEquals('4.0000', $data['tax_amount']);
        $this->assertEquals('44.0000', $data['total_amount']);
    }

    public function test_requires_at_least_one_line(): void
    {
        $response = $this->postJson('/api/v1/sales/orders', array_merge($this->defaultOrder, [
            'lines' => [],
        ]));

        $response->assertStatus(422);
    }

    public function test_can_list_sales_orders(): void
    {
        $this->postJson('/api/v1/sales/orders', $this->defaultOrder);
        $this->postJson('/api/v1/sales/orders', array_merge($this->defaultOrder, [
            'customer_name' => 'Beta Corp',
        ]));

        $response = $this->getJson("/api/v1/sales/orders?tenant_id={$this->tenantId}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success', 'message', 'data', 'errors',
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            ]);

        $this->assertCount(2, $response->json('data'));
    }

    public function test_can_get_sales_order_by_id(): void
    {
        $createResponse = $this->postJson('/api/v1/sales/orders', $this->defaultOrder);
        $id = $createResponse->json('data.id');

        $response = $this->getJson("/api/v1/sales/orders/{$id}?tenant_id={$this->tenantId}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $id)
            ->assertJsonPath('data.customer_name', 'Acme Corp');
    }

    public function test_returns_404_for_nonexistent_order(): void
    {
        $response = $this->getJson("/api/v1/sales/orders/99999?tenant_id={$this->tenantId}");

        $response->assertStatus(404)
            ->assertJsonPath('success', false);
    }

    public function test_can_confirm_draft_order(): void
    {
        $createResponse = $this->postJson('/api/v1/sales/orders', $this->defaultOrder);
        $id = $createResponse->json('data.id');

        $response = $this->postJson("/api/v1/sales/orders/{$id}/confirm?tenant_id={$this->tenantId}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'confirmed');
    }

    public function test_cannot_confirm_already_confirmed_order(): void
    {
        $createResponse = $this->postJson('/api/v1/sales/orders', $this->defaultOrder);
        $id = $createResponse->json('data.id');

        $this->postJson("/api/v1/sales/orders/{$id}/confirm?tenant_id={$this->tenantId}");
        $response = $this->postJson("/api/v1/sales/orders/{$id}/confirm?tenant_id={$this->tenantId}");

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_can_cancel_draft_order(): void
    {
        $createResponse = $this->postJson('/api/v1/sales/orders', $this->defaultOrder);
        $id = $createResponse->json('data.id');

        $response = $this->postJson("/api/v1/sales/orders/{$id}/cancel?tenant_id={$this->tenantId}", [
            'reason' => 'Customer request',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'cancelled');
    }

    public function test_can_cancel_confirmed_order(): void
    {
        $createResponse = $this->postJson('/api/v1/sales/orders', $this->defaultOrder);
        $id = $createResponse->json('data.id');

        $this->postJson("/api/v1/sales/orders/{$id}/confirm?tenant_id={$this->tenantId}");

        $response = $this->postJson("/api/v1/sales/orders/{$id}/cancel?tenant_id={$this->tenantId}");

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'cancelled');
    }

    public function test_cannot_cancel_already_cancelled_order(): void
    {
        $createResponse = $this->postJson('/api/v1/sales/orders', $this->defaultOrder);
        $id = $createResponse->json('data.id');

        $this->postJson("/api/v1/sales/orders/{$id}/cancel?tenant_id={$this->tenantId}");
        $response = $this->postJson("/api/v1/sales/orders/{$id}/cancel?tenant_id={$this->tenantId}");

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_can_delete_sales_order(): void
    {
        $createResponse = $this->postJson('/api/v1/sales/orders', $this->defaultOrder);
        $id = $createResponse->json('data.id');

        $response = $this->deleteJson("/api/v1/sales/orders/{$id}?tenant_id={$this->tenantId}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->getJson("/api/v1/sales/orders/{$id}?tenant_id={$this->tenantId}")
            ->assertStatus(404);
    }

    public function test_order_number_is_unique_per_tenant(): void
    {
        $response1 = $this->postJson('/api/v1/sales/orders', $this->defaultOrder);
        $response2 = $this->postJson('/api/v1/sales/orders', $this->defaultOrder);

        $response1->assertStatus(201);
        $response2->assertStatus(201);

        $this->assertNotEquals(
            $response1->json('data.order_number'),
            $response2->json('data.order_number')
        );
    }

    public function test_customer_name_is_required(): void
    {
        $data = $this->defaultOrder;
        unset($data['customer_name']);

        $response = $this->postJson('/api/v1/sales/orders', $data);

        $response->assertStatus(422);
    }

    public function test_order_date_is_required(): void
    {
        $data = $this->defaultOrder;
        unset($data['order_date']);

        $response = $this->postJson('/api/v1/sales/orders', $data);

        $response->assertStatus(422);
    }

    public function test_line_quantity_must_be_positive(): void
    {
        $data = $this->defaultOrder;
        $data['lines'][0]['quantity'] = '0';

        $response = $this->postJson('/api/v1/sales/orders', $data);

        $response->assertStatus(422);
    }

    public function test_discount_rate_cannot_exceed_100(): void
    {
        $data = $this->defaultOrder;
        $data['lines'][0]['discount_rate'] = '101';

        $response = $this->postJson('/api/v1/sales/orders', $data);

        $response->assertStatus(422);
    }

    public function test_currency_defaults_to_system_default(): void
    {
        $data = $this->defaultOrder;
        unset($data['currency']);

        $response = $this->postJson('/api/v1/sales/orders', $data);

        $response->assertStatus(201)
            ->assertJsonPath('data.currency', config('currency.default', 'LKR'));
    }

    public function test_currency_must_be_supported(): void
    {
        $data = $this->defaultOrder;
        $data['currency'] = 'XYZ';

        $response = $this->postJson('/api/v1/sales/orders', $data);

        $response->assertStatus(422);
    }
}
