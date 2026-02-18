<?php

declare(strict_types=1);

namespace Modules\Sales\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Inventory\Entities\Product;
use Modules\Sales\Entities\Customer;
use Modules\Sales\Entities\Quote;
use Modules\Sales\Entities\SalesOrder;
use Modules\Tenancy\Entities\Tenant;
use Tests\TestCase;

/**
 * Order Workflow Tests
 *
 * Tests for complete sales order lifecycle
 */
class OrderWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Tenant $tenant;
    protected Customer $customer;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();
        tenancy()->initialize($this->tenant);

        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'permissions' => [
                'order.view',
                'order.create',
                'order.update',
                'order.delete',
                'order.confirm',
                'order.cancel',
            ],
        ]);

        $this->customer = Customer::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $this->product = Product::factory()->create([
            'tenant_id' => $this->tenant->id,
            'unit_price' => 100.00,
            'stock_quantity' => 50,
        ]);
    }

    public function test_it_creates_draft_order(): void
    {
        $orderData = [
            'customer_id' => $this->customer->id,
            'status' => 'draft',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 5,
                    'unit_price' => 100.00,
                ],
            ],
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/sales/orders', $orderData);

        $response->assertStatus(201)
            ->assertJsonPath('data.status', 'draft')
            ->assertJsonPath('data.customer_id', $this->customer->id);

        $this->assertDatabaseHas('sales_orders', [
            'customer_id' => $this->customer->id,
            'status' => 'draft',
            'tenant_id' => $this->tenant->id,
        ]);
    }

    public function test_it_confirms_draft_order(): void
    {
        $order = SalesOrder::factory()->create([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $this->customer->id,
            'status' => 'draft',
        ]);

        $response = $this->actingAs($this->user)
            ->putJson("/api/v1/sales/orders/{$order->id}/confirm");

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'confirmed');

        $this->assertDatabaseHas('sales_orders', [
            'id' => $order->id,
            'status' => 'confirmed',
        ]);
    }

    public function test_it_calculates_order_total_correctly(): void
    {
        $orderData = [
            'customer_id' => $this->customer->id,
            'status' => 'draft',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 5,
                    'unit_price' => 100.00,
                    'discount_percentage' => 10,
                ],
            ],
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/sales/orders', $orderData);

        $response->assertStatus(201);

        $subtotal = 5 * 100.00; // 500.00
        $discount = $subtotal * 0.10; // 50.00
        $expectedTotal = $subtotal - $discount; // 450.00

        $response->assertJsonPath('data.subtotal', 500.00)
            ->assertJsonPath('data.total', 450.00);
    }

    public function test_it_prevents_confirming_order_without_items(): void
    {
        $order = SalesOrder::factory()->create([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $this->customer->id,
            'status' => 'draft',
        ]);

        // Don't add any order lines

        $response = $this->actingAs($this->user)
            ->putJson("/api/v1/sales/orders/{$order->id}/confirm");

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['items']);
    }

    public function test_it_checks_stock_availability_on_confirm(): void
    {
        $order = SalesOrder::factory()->create([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $this->customer->id,
            'status' => 'draft',
        ]);

        // Add order line with quantity exceeding stock
        $order->lines()->create([
            'product_id' => $this->product->id,
            'quantity' => 100, // Only 50 available
            'unit_price' => 100.00,
        ]);

        $response = $this->actingAs($this->user)
            ->putJson("/api/v1/sales/orders/{$order->id}/confirm");

        $response->assertStatus(422)
            ->assertJsonFragment(['message' => 'Insufficient stock']);
    }

    public function test_it_reserves_stock_on_confirmation(): void
    {
        $initialStock = $this->product->stock_quantity;

        $order = SalesOrder::factory()->create([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $this->customer->id,
            'status' => 'draft',
        ]);

        $order->lines()->create([
            'product_id' => $this->product->id,
            'quantity' => 10,
            'unit_price' => 100.00,
        ]);

        $response = $this->actingAs($this->user)
            ->putJson("/api/v1/sales/orders/{$order->id}/confirm");

        $response->assertStatus(200);

        // Check that stock is reserved (not yet deducted)
        $this->product->refresh();
        $expectedReserved = 10;

        $this->assertEquals($expectedReserved, $this->product->reserved_quantity);
    }

    public function test_it_cancels_order_and_releases_stock(): void
    {
        $order = SalesOrder::factory()->create([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $this->customer->id,
            'status' => 'confirmed',
        ]);

        $order->lines()->create([
            'product_id' => $this->product->id,
            'quantity' => 10,
            'unit_price' => 100.00,
        ]);

        // Reserve stock
        $this->product->update(['reserved_quantity' => 10]);

        $response = $this->actingAs($this->user)
            ->putJson("/api/v1/sales/orders/{$order->id}/cancel");

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'cancelled');

        // Check that reserved stock is released
        $this->product->refresh();
        $this->assertEquals(0, $this->product->reserved_quantity);
    }

    public function test_it_prevents_modifying_confirmed_order(): void
    {
        $order = SalesOrder::factory()->create([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $this->customer->id,
            'status' => 'confirmed',
        ]);

        $updateData = [
            'customer_id' => Customer::factory()->create(['tenant_id' => $this->tenant->id])->id,
        ];

        $response = $this->actingAs($this->user)
            ->putJson("/api/v1/sales/orders/{$order->id}", $updateData);

        $response->assertStatus(422)
            ->assertJsonFragment(['message' => 'Cannot modify confirmed order']);
    }

    public function test_it_marks_order_as_delivered(): void
    {
        $order = SalesOrder::factory()->create([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $this->customer->id,
            'status' => 'confirmed',
        ]);

        $response = $this->actingAs($this->user)
            ->putJson("/api/v1/sales/orders/{$order->id}/deliver");

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'delivered');

        $this->assertDatabaseHas('sales_orders', [
            'id' => $order->id,
            'status' => 'delivered',
        ]);
    }

    public function test_it_deducts_stock_on_delivery(): void
    {
        $initialStock = $this->product->stock_quantity;

        $order = SalesOrder::factory()->create([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $this->customer->id,
            'status' => 'confirmed',
        ]);

        $order->lines()->create([
            'product_id' => $this->product->id,
            'quantity' => 10,
            'unit_price' => 100.00,
        ]);

        $response = $this->actingAs($this->user)
            ->putJson("/api/v1/sales/orders/{$order->id}/deliver");

        $response->assertStatus(200);

        // Check that stock is deducted
        $this->product->refresh();
        $expectedStock = $initialStock - 10;

        $this->assertEquals($expectedStock, $this->product->stock_quantity);
    }

    public function test_it_generates_invoice_from_order(): void
    {
        $order = SalesOrder::factory()->create([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $this->customer->id,
            'status' => 'delivered',
            'total' => 500.00,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/sales/orders/{$order->id}/invoice");

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'invoice_number',
                    'customer_id',
                    'total',
                ],
            ]);

        $this->assertDatabaseHas('invoices', [
            'customer_id' => $this->customer->id,
            'total' => 500.00,
        ]);
    }

    public function test_it_tracks_order_status_history(): void
    {
        $order = SalesOrder::factory()->create([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $this->customer->id,
            'status' => 'draft',
        ]);

        // Confirm order
        $this->actingAs($this->user)
            ->putJson("/api/v1/sales/orders/{$order->id}/confirm");

        // Deliver order
        $this->actingAs($this->user)
            ->putJson("/api/v1/sales/orders/{$order->id}/deliver");

        $order->refresh();

        // Check status history (if implemented)
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/sales/orders/{$order->id}/history");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'status',
                        'changed_at',
                        'changed_by',
                    ],
                ],
            ]);
    }

    public function test_it_prevents_cross_tenant_order_access(): void
    {
        // Create order for tenant1
        $order = SalesOrder::factory()->create([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $this->customer->id,
        ]);

        // Create different tenant and user
        $tenant2 = Tenant::factory()->create();
        tenancy()->initialize($tenant2);

        $user2 = User::factory()->create([
            'tenant_id' => $tenant2->id,
            'permissions' => ['order.view'],
        ]);

        // Try to access tenant1's order from tenant2
        $response = $this->actingAs($user2)
            ->getJson("/api/v1/sales/orders/{$order->id}");

        $response->assertStatus(404);
    }

    public function test_it_calculates_order_totals_with_tax(): void
    {
        $orderData = [
            'customer_id' => $this->customer->id,
            'status' => 'draft',
            'tax_rate' => 10, // 10% tax
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 5,
                    'unit_price' => 100.00,
                ],
            ],
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/sales/orders', $orderData);

        $response->assertStatus(201);

        $subtotal = 500.00;
        $tax = $subtotal * 0.10; // 50.00
        $expectedTotal = $subtotal + $tax; // 550.00

        $response->assertJsonPath('data.subtotal', 500.00)
            ->assertJsonPath('data.tax_amount', 50.00)
            ->assertJsonPath('data.total', 550.00);
    }

    public function test_it_applies_customer_credit_limit(): void
    {
        $customer = Customer::factory()->create([
            'tenant_id' => $this->tenant->id,
            'credit_limit' => 1000.00,
            'current_balance' => 900.00, // Only $100 credit available
        ]);

        $orderData = [
            'customer_id' => $customer->id,
            'status' => 'draft',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 5,
                    'unit_price' => 100.00, // Total: $500
                ],
            ],
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/sales/orders/confirm', $orderData);

        $response->assertStatus(422)
            ->assertJsonFragment(['message' => 'Exceeds customer credit limit']);
    }
}
