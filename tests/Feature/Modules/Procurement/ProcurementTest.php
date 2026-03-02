<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Procurement;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProcurementTest extends TestCase
{
    use RefreshDatabase;

    private int $tenantId;

    private int $warehouseId;

    private int $productId;

    private int $supplierId;

    private array $defaultOrderLine;

    protected function setUp(): void
    {
        parent::setUp();

        // Create tenant
        $tenantResponse = $this->postJson('/api/v1/tenants', [
            'name' => 'Procurement Test Tenant',
            'slug' => 'procurement-test',
        ]);
        $this->tenantId = $tenantResponse->json('data.id');

        // Create warehouse
        $warehouseResponse = $this->postJson('/api/v1/warehouses', [
            'tenant_id' => $this->tenantId,
            'name' => 'Main Warehouse',
            'code' => 'MW-001',
        ]);
        $this->warehouseId = $warehouseResponse->json('data.id');

        // Create product
        $productResponse = $this->postJson('/api/v1/products', [
            'tenant_id' => $this->tenantId,
            'sku' => 'PROC-ITEM-001',
            'name' => 'Procured Item',
            'type' => 'stockable',
            'uom' => 'pcs',
            'costing_method' => 'fifo',
            'cost_price' => '5.00',
            'sale_price' => '10.00',
        ]);
        $this->productId = $productResponse->json('data.id');

        // Create supplier
        $supplierResponse = $this->postJson('/api/v1/suppliers', [
            'tenant_id' => $this->tenantId,
            'name' => 'Test Supplier Ltd',
            'contact_name' => 'John Doe',
            'email' => 'supplier@example.com',
            'phone' => '+94771234567',
        ]);
        $this->supplierId = $supplierResponse->json('data.id');

        $this->defaultOrderLine = [
            'product_id' => $this->productId,
            'quantity' => 100,
            'unit_cost' => '5.00',
        ];
    }

    // ─── Supplier Tests ───────────────────────────────────────────────────────

    public function test_can_create_supplier(): void
    {
        $response = $this->postJson('/api/v1/suppliers', [
            'tenant_id' => $this->tenantId,
            'name' => 'Another Supplier',
            'contact_name' => 'Jane Doe',
            'email' => 'jane@supplier.com',
            'phone' => '+94779999999',
            'address' => '123 Main St, Colombo',
            'notes' => 'Preferred supplier',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success', 'message',
                'data' => ['id', 'tenant_id', 'name', 'contact_name', 'email', 'phone', 'address', 'status', 'notes', 'created_at'],
                'errors',
            ])
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Another Supplier')
            ->assertJsonPath('data.status', 'active')
            ->assertJsonPath('data.tenant_id', $this->tenantId);
    }

    public function test_can_list_suppliers(): void
    {
        $this->postJson('/api/v1/suppliers', [
            'tenant_id' => $this->tenantId,
            'name' => 'Supplier A',
        ]);
        $this->postJson('/api/v1/suppliers', [
            'tenant_id' => $this->tenantId,
            'name' => 'Supplier B',
        ]);

        $response = $this->getJson("/api/v1/suppliers?tenant_id={$this->tenantId}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        // setUp created one + 2 more = 3 total
        $this->assertGreaterThanOrEqual(3, count($response->json('data')));
    }

    public function test_can_get_supplier_by_id(): void
    {
        $response = $this->getJson("/api/v1/suppliers/{$this->supplierId}?tenant_id={$this->tenantId}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $this->supplierId)
            ->assertJsonPath('data.name', 'Test Supplier Ltd');
    }

    public function test_returns_404_for_nonexistent_supplier(): void
    {
        $response = $this->getJson("/api/v1/suppliers/99999?tenant_id={$this->tenantId}");

        $response->assertStatus(404)
            ->assertJsonPath('success', false);
    }

    public function test_can_update_supplier(): void
    {
        $response = $this->putJson(
            "/api/v1/suppliers/{$this->supplierId}?tenant_id={$this->tenantId}",
            [
                'name' => 'Updated Supplier Ltd',
                'status' => 'inactive',
            ]
        );

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Updated Supplier Ltd')
            ->assertJsonPath('data.status', 'inactive');
    }

    public function test_can_delete_supplier(): void
    {
        $response = $this->deleteJson(
            "/api/v1/suppliers/{$this->supplierId}?tenant_id={$this->tenantId}"
        );

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->getJson("/api/v1/suppliers/{$this->supplierId}?tenant_id={$this->tenantId}")
            ->assertStatus(404);
    }

    public function test_supplier_name_is_required(): void
    {
        $response = $this->postJson('/api/v1/suppliers', [
            'tenant_id' => $this->tenantId,
        ]);

        $response->assertStatus(422);
    }

    // ─── Purchase Order Tests ─────────────────────────────────────────────────

    public function test_can_create_purchase_order(): void
    {
        $response = $this->postJson('/api/v1/procurement/orders', [
            'tenant_id' => $this->tenantId,
            'supplier_id' => $this->supplierId,
            'order_date' => '2026-01-15',
            'lines' => [$this->defaultOrderLine],
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success', 'message',
                'data' => [
                    'id', 'tenant_id', 'supplier_id', 'order_number', 'status',
                    'order_date', 'currency', 'subtotal', 'tax_amount',
                    'discount_amount', 'total_amount', 'lines', 'created_at',
                ],
                'errors',
            ])
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'draft')
            ->assertJsonPath('data.tenant_id', $this->tenantId)
            ->assertJsonPath('data.supplier_id', $this->supplierId);
    }

    public function test_purchase_order_status_is_draft_on_creation(): void
    {
        $response = $this->postJson('/api/v1/procurement/orders', [
            'tenant_id' => $this->tenantId,
            'supplier_id' => $this->supplierId,
            'order_date' => '2026-01-15',
            'lines' => [$this->defaultOrderLine],
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.status', 'draft');
    }

    public function test_purchase_order_number_is_generated(): void
    {
        $response = $this->postJson('/api/v1/procurement/orders', [
            'tenant_id' => $this->tenantId,
            'supplier_id' => $this->supplierId,
            'order_date' => '2026-01-15',
            'lines' => [$this->defaultOrderLine],
        ]);

        $orderNumber = $response->json('data.order_number');
        $this->assertStringStartsWith("PO-{$this->tenantId}-", $orderNumber);
    }

    public function test_line_totals_are_calculated_with_bcmath_precision(): void
    {
        $response = $this->postJson('/api/v1/procurement/orders', [
            'tenant_id' => $this->tenantId,
            'supplier_id' => $this->supplierId,
            'order_date' => '2026-01-15',
            'lines' => [
                [
                    'product_id' => $this->productId,
                    'quantity' => 3,
                    'unit_cost' => '10.1234',
                    'tax_rate' => 10,
                    'discount_rate' => 5,
                ],
            ],
        ]);

        $response->assertStatus(201);
        $line = $response->json('data.lines.0');

        // gross = 3 × 10.1234 = 30.3702
        // discount = 30.3702 × (5/100) = 1.5185
        // after_disc = 30.3702 - 1.5185 = 28.8517
        // tax = 28.8517 × (10/100) = 2.8851 (BCMath 4dp truncation)
        // line_total = 28.8517 + 2.8851 = 31.7368
        $this->assertEquals('31.7368', $line['line_total']);
    }

    public function test_requires_at_least_one_line(): void
    {
        $response = $this->postJson('/api/v1/procurement/orders', [
            'tenant_id' => $this->tenantId,
            'supplier_id' => $this->supplierId,
            'order_date' => '2026-01-15',
            'lines' => [],
        ]);

        $response->assertStatus(422);
    }

    public function test_rejects_nonexistent_supplier(): void
    {
        $response = $this->postJson('/api/v1/procurement/orders', [
            'tenant_id' => $this->tenantId,
            'supplier_id' => 99999,
            'order_date' => '2026-01-15',
            'lines' => [$this->defaultOrderLine],
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_can_list_purchase_orders(): void
    {
        $this->postJson('/api/v1/procurement/orders', [
            'tenant_id' => $this->tenantId,
            'supplier_id' => $this->supplierId,
            'order_date' => '2026-01-15',
            'lines' => [$this->defaultOrderLine],
        ]);

        $response = $this->getJson("/api/v1/procurement/orders?tenant_id={$this->tenantId}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success', 'message', 'data', 'errors',
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            ]);

        $this->assertGreaterThanOrEqual(1, count($response->json('data')));
    }

    public function test_can_get_purchase_order_by_id(): void
    {
        $createResponse = $this->postJson('/api/v1/procurement/orders', [
            'tenant_id' => $this->tenantId,
            'supplier_id' => $this->supplierId,
            'order_date' => '2026-01-15',
            'lines' => [$this->defaultOrderLine],
        ]);
        $orderId = $createResponse->json('data.id');

        $response = $this->getJson("/api/v1/procurement/orders/{$orderId}?tenant_id={$this->tenantId}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $orderId);
    }

    public function test_returns_404_for_nonexistent_order(): void
    {
        $response = $this->getJson("/api/v1/procurement/orders/99999?tenant_id={$this->tenantId}");

        $response->assertStatus(404)
            ->assertJsonPath('success', false);
    }

    public function test_can_confirm_draft_order(): void
    {
        $createResponse = $this->postJson('/api/v1/procurement/orders', [
            'tenant_id' => $this->tenantId,
            'supplier_id' => $this->supplierId,
            'order_date' => '2026-01-15',
            'lines' => [$this->defaultOrderLine],
        ]);
        $orderId = $createResponse->json('data.id');

        $response = $this->postJson(
            "/api/v1/procurement/orders/{$orderId}/confirm?tenant_id={$this->tenantId}"
        );

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'confirmed');
    }

    public function test_cannot_confirm_already_confirmed_order(): void
    {
        $createResponse = $this->postJson('/api/v1/procurement/orders', [
            'tenant_id' => $this->tenantId,
            'supplier_id' => $this->supplierId,
            'order_date' => '2026-01-15',
            'lines' => [$this->defaultOrderLine],
        ]);
        $orderId = $createResponse->json('data.id');

        $this->postJson("/api/v1/procurement/orders/{$orderId}/confirm?tenant_id={$this->tenantId}");

        $response = $this->postJson(
            "/api/v1/procurement/orders/{$orderId}/confirm?tenant_id={$this->tenantId}"
        );

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_can_cancel_draft_order(): void
    {
        $createResponse = $this->postJson('/api/v1/procurement/orders', [
            'tenant_id' => $this->tenantId,
            'supplier_id' => $this->supplierId,
            'order_date' => '2026-01-15',
            'lines' => [$this->defaultOrderLine],
        ]);
        $orderId = $createResponse->json('data.id');

        $response = $this->postJson(
            "/api/v1/procurement/orders/{$orderId}/cancel?tenant_id={$this->tenantId}",
            ['reason' => 'Budget freeze']
        );

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'cancelled');
    }

    public function test_can_cancel_confirmed_order(): void
    {
        $createResponse = $this->postJson('/api/v1/procurement/orders', [
            'tenant_id' => $this->tenantId,
            'supplier_id' => $this->supplierId,
            'order_date' => '2026-01-15',
            'lines' => [$this->defaultOrderLine],
        ]);
        $orderId = $createResponse->json('data.id');

        $this->postJson("/api/v1/procurement/orders/{$orderId}/confirm?tenant_id={$this->tenantId}");

        $response = $this->postJson(
            "/api/v1/procurement/orders/{$orderId}/cancel?tenant_id={$this->tenantId}",
            ['reason' => 'Supplier unavailable']
        );

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'cancelled');
    }

    public function test_cannot_cancel_already_cancelled_order(): void
    {
        $createResponse = $this->postJson('/api/v1/procurement/orders', [
            'tenant_id' => $this->tenantId,
            'supplier_id' => $this->supplierId,
            'order_date' => '2026-01-15',
            'lines' => [$this->defaultOrderLine],
        ]);
        $orderId = $createResponse->json('data.id');

        $this->postJson("/api/v1/procurement/orders/{$orderId}/cancel?tenant_id={$this->tenantId}");

        $response = $this->postJson(
            "/api/v1/procurement/orders/{$orderId}/cancel?tenant_id={$this->tenantId}"
        );

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_cannot_receive_goods_on_draft_order(): void
    {
        $createResponse = $this->postJson('/api/v1/procurement/orders', [
            'tenant_id' => $this->tenantId,
            'supplier_id' => $this->supplierId,
            'order_date' => '2026-01-15',
            'lines' => [$this->defaultOrderLine],
        ]);
        $orderId = $createResponse->json('data.id');
        $lineId = $createResponse->json('data.lines.0.id');

        $response = $this->postJson(
            "/api/v1/procurement/orders/{$orderId}/receive?tenant_id={$this->tenantId}",
            [
                'warehouse_id' => $this->warehouseId,
                'received_lines' => [['line_id' => $lineId, 'quantity_received' => 50]],
            ]
        );

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_can_receive_goods_on_confirmed_order_and_stock_is_updated(): void
    {
        $createResponse = $this->postJson('/api/v1/procurement/orders', [
            'tenant_id' => $this->tenantId,
            'supplier_id' => $this->supplierId,
            'order_date' => '2026-01-15',
            'lines' => [
                [
                    'product_id' => $this->productId,
                    'quantity' => 100,
                    'unit_cost' => '5.00',
                ],
            ],
        ]);
        $orderId = $createResponse->json('data.id');
        $lineId = $createResponse->json('data.lines.0.id');

        $this->postJson("/api/v1/procurement/orders/{$orderId}/confirm?tenant_id={$this->tenantId}");

        $response = $this->postJson(
            "/api/v1/procurement/orders/{$orderId}/receive?tenant_id={$this->tenantId}",
            [
                'warehouse_id' => $this->warehouseId,
                'received_lines' => [['line_id' => $lineId, 'quantity_received' => 100]],
            ]
        );

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'received')
            ->assertJsonPath('data.lines.0.quantity_received', '100.0000');

        // Verify stock was created in inventory
        $stockResponse = $this->getJson(
            "/api/v1/inventory/stock?tenant_id={$this->tenantId}&warehouse_id={$this->warehouseId}"
        );
        $stockResponse->assertStatus(200);
        $stockItems = $stockResponse->json('data');
        $productStock = collect($stockItems)->firstWhere('product_id', $this->productId);
        $this->assertNotNull($productStock);
        $this->assertEquals('100.0000', $productStock['quantity_on_hand']);
    }

    public function test_partial_receipt_sets_status_to_partially_received(): void
    {
        $createResponse = $this->postJson('/api/v1/procurement/orders', [
            'tenant_id' => $this->tenantId,
            'supplier_id' => $this->supplierId,
            'order_date' => '2026-01-15',
            'lines' => [
                [
                    'product_id' => $this->productId,
                    'quantity' => 100,
                    'unit_cost' => '5.00',
                ],
            ],
        ]);
        $orderId = $createResponse->json('data.id');
        $lineId = $createResponse->json('data.lines.0.id');

        $this->postJson("/api/v1/procurement/orders/{$orderId}/confirm?tenant_id={$this->tenantId}");

        $response = $this->postJson(
            "/api/v1/procurement/orders/{$orderId}/receive?tenant_id={$this->tenantId}",
            [
                'warehouse_id' => $this->warehouseId,
                'received_lines' => [['line_id' => $lineId, 'quantity_received' => 50]],
            ]
        );

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'partially_received')
            ->assertJsonPath('data.lines.0.quantity_received', '50.0000');
    }

    public function test_cannot_receive_more_than_ordered_quantity(): void
    {
        $createResponse = $this->postJson('/api/v1/procurement/orders', [
            'tenant_id' => $this->tenantId,
            'supplier_id' => $this->supplierId,
            'order_date' => '2026-01-15',
            'lines' => [
                [
                    'product_id' => $this->productId,
                    'quantity' => 10,
                    'unit_cost' => '5.00',
                ],
            ],
        ]);
        $orderId = $createResponse->json('data.id');
        $lineId = $createResponse->json('data.lines.0.id');

        $this->postJson("/api/v1/procurement/orders/{$orderId}/confirm?tenant_id={$this->tenantId}");

        $response = $this->postJson(
            "/api/v1/procurement/orders/{$orderId}/receive?tenant_id={$this->tenantId}",
            [
                'warehouse_id' => $this->warehouseId,
                'received_lines' => [['line_id' => $lineId, 'quantity_received' => 20]],
            ]
        );

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_can_delete_purchase_order(): void
    {
        $createResponse = $this->postJson('/api/v1/procurement/orders', [
            'tenant_id' => $this->tenantId,
            'supplier_id' => $this->supplierId,
            'order_date' => '2026-01-15',
            'lines' => [$this->defaultOrderLine],
        ]);
        $orderId = $createResponse->json('data.id');

        $response = $this->deleteJson(
            "/api/v1/procurement/orders/{$orderId}?tenant_id={$this->tenantId}"
        );

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->getJson("/api/v1/procurement/orders/{$orderId}?tenant_id={$this->tenantId}")
            ->assertStatus(404);
    }

    public function test_order_number_is_unique_per_tenant(): void
    {
        $order1 = $this->postJson('/api/v1/procurement/orders', [
            'tenant_id' => $this->tenantId,
            'supplier_id' => $this->supplierId,
            'order_date' => '2026-01-15',
            'lines' => [$this->defaultOrderLine],
        ]);

        $order2 = $this->postJson('/api/v1/procurement/orders', [
            'tenant_id' => $this->tenantId,
            'supplier_id' => $this->supplierId,
            'order_date' => '2026-01-16',
            'lines' => [$this->defaultOrderLine],
        ]);

        $this->assertNotEquals(
            $order1->json('data.order_number'),
            $order2->json('data.order_number')
        );
    }

    public function test_currency_defaults_to_system_default(): void
    {
        $response = $this->postJson('/api/v1/procurement/orders', [
            'tenant_id' => $this->tenantId,
            'supplier_id' => $this->supplierId,
            'order_date' => '2026-01-15',
            'lines' => [$this->defaultOrderLine],
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.currency', config('currency.default', 'LKR'));
    }

    public function test_supplier_id_is_required_for_purchase_order(): void
    {
        $response = $this->postJson('/api/v1/procurement/orders', [
            'tenant_id' => $this->tenantId,
            'order_date' => '2026-01-15',
            'lines' => [$this->defaultOrderLine],
        ]);

        $response->assertStatus(422);
    }

    public function test_order_date_is_required(): void
    {
        $response = $this->postJson('/api/v1/procurement/orders', [
            'tenant_id' => $this->tenantId,
            'supplier_id' => $this->supplierId,
            'lines' => [$this->defaultOrderLine],
        ]);

        $response->assertStatus(422);
    }

    public function test_line_quantity_must_be_positive(): void
    {
        $response = $this->postJson('/api/v1/procurement/orders', [
            'tenant_id' => $this->tenantId,
            'supplier_id' => $this->supplierId,
            'order_date' => '2026-01-15',
            'lines' => [
                array_merge($this->defaultOrderLine, ['quantity' => 0]),
            ],
        ]);

        $response->assertStatus(422);
    }

    public function test_discount_rate_cannot_exceed_100(): void
    {
        $response = $this->postJson('/api/v1/procurement/orders', [
            'tenant_id' => $this->tenantId,
            'supplier_id' => $this->supplierId,
            'order_date' => '2026-01-15',
            'lines' => [
                array_merge($this->defaultOrderLine, ['discount_rate' => 150]),
            ],
        ]);

        $response->assertStatus(422);
    }
}
