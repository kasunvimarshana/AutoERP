<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Ecommerce;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EcommerceTest extends TestCase
{
    use RefreshDatabase;

    private int $tenantId;

    protected function setUp(): void
    {
        parent::setUp();

        $tenantResponse = $this->postJson('/api/v1/tenants', [
            'name' => 'Ecommerce Test Tenant',
            'slug' => 'ecommerce-test-tenant',
        ]);
        $this->tenantId = $tenantResponse->json('data.id');
    }

    // ─────────────────────────────────────────────
    // StorefrontProduct tests
    // ─────────────────────────────────────────────

    public function test_can_create_storefront_product(): void
    {
        $response = $this->postJson('/api/v1/ecommerce/products', [
            'tenant_id' => $this->tenantId,
            'product_id' => 1,
            'slug' => 'test-product',
            'name' => 'Test Product',
            'description' => 'A test product',
            'price' => '99.99',
            'currency' => 'LKR',
            'is_active' => true,
            'is_featured' => false,
            'sort_order' => 1,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.tenant_id', $this->tenantId)
            ->assertJsonPath('data.slug', 'test-product')
            ->assertJsonPath('data.name', 'Test Product')
            ->assertJsonPath('data.currency', 'LKR')
            ->assertJsonPath('data.is_active', true)
            ->assertJsonStructure(['data' => [
                'id', 'tenant_id', 'product_id', 'slug', 'name',
                'price', 'currency', 'is_active', 'is_featured',
                'sort_order', 'created_at', 'updated_at',
            ]]);
    }

    public function test_storefront_product_slug_must_be_unique_per_tenant(): void
    {
        $payload = [
            'tenant_id' => $this->tenantId,
            'product_id' => 1,
            'slug' => 'duplicate-slug',
            'name' => 'Product One',
            'price' => '50.00',
            'currency' => 'LKR',
        ];

        $this->postJson('/api/v1/ecommerce/products', $payload)->assertStatus(201);
        $this->postJson('/api/v1/ecommerce/products', $payload)->assertStatus(422);
    }

    public function test_can_list_storefront_products(): void
    {
        $this->postJson('/api/v1/ecommerce/products', [
            'tenant_id' => $this->tenantId,
            'product_id' => 1,
            'slug' => 'product-a',
            'name' => 'Product A',
            'price' => '10.00',
            'currency' => 'LKR',
        ])->assertStatus(201);

        $response = $this->getJson("/api/v1/ecommerce/products?tenant_id={$this->tenantId}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data', 'meta' => ['total', 'current_page', 'last_page', 'per_page']]);

        $this->assertGreaterThanOrEqual(1, count($response->json('data')));
    }

    public function test_can_get_storefront_product_by_id(): void
    {
        $create = $this->postJson('/api/v1/ecommerce/products', [
            'tenant_id' => $this->tenantId,
            'product_id' => 1,
            'slug' => 'get-by-id',
            'name' => 'Get By ID',
            'price' => '25.00',
            'currency' => 'LKR',
        ]);
        $id = $create->json('data.id');

        $response = $this->getJson("/api/v1/ecommerce/products/{$id}?tenant_id={$this->tenantId}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $id)
            ->assertJsonPath('data.slug', 'get-by-id');
    }

    public function test_returns_404_for_missing_product(): void
    {
        $response = $this->getJson("/api/v1/ecommerce/products/99999?tenant_id={$this->tenantId}");

        $response->assertStatus(404);
    }

    public function test_can_get_featured_products(): void
    {
        $this->postJson('/api/v1/ecommerce/products', [
            'tenant_id' => $this->tenantId,
            'product_id' => 1,
            'slug' => 'featured-product',
            'name' => 'Featured Product',
            'price' => '75.00',
            'currency' => 'LKR',
            'is_featured' => true,
            'is_active' => true,
        ])->assertStatus(201);

        $response = $this->getJson("/api/v1/ecommerce/products/featured?tenant_id={$this->tenantId}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertGreaterThanOrEqual(1, count($response->json('data')));
    }

    public function test_can_update_storefront_product(): void
    {
        $create = $this->postJson('/api/v1/ecommerce/products', [
            'tenant_id' => $this->tenantId,
            'product_id' => 1,
            'slug' => 'update-me',
            'name' => 'Original Name',
            'price' => '10.00',
            'currency' => 'LKR',
        ]);
        $id = $create->json('data.id');

        $response = $this->putJson("/api/v1/ecommerce/products/{$id}", [
            'tenant_id' => $this->tenantId,
            'slug' => 'update-me',
            'name' => 'Updated Name',
            'price' => '20.00',
            'currency' => 'LKR',
            'is_active' => true,
            'is_featured' => false,
            'sort_order' => 0,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Updated Name')
            ->assertJsonPath('data.price', '20.0000');
    }

    public function test_can_delete_storefront_product(): void
    {
        $create = $this->postJson('/api/v1/ecommerce/products', [
            'tenant_id' => $this->tenantId,
            'product_id' => 1,
            'slug' => 'delete-me',
            'name' => 'Delete Me',
            'price' => '5.00',
            'currency' => 'LKR',
        ]);
        $id = $create->json('data.id');

        $this->deleteJson("/api/v1/ecommerce/products/{$id}?tenant_id={$this->tenantId}")
            ->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->getJson("/api/v1/ecommerce/products/{$id}?tenant_id={$this->tenantId}")
            ->assertStatus(404);
    }

    // ─────────────────────────────────────────────
    // Cart tests
    // ─────────────────────────────────────────────

    public function test_can_create_cart(): void
    {
        $response = $this->postJson('/api/v1/ecommerce/carts', [
            'tenant_id' => $this->tenantId,
            'currency' => 'LKR',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.tenant_id', $this->tenantId)
            ->assertJsonPath('data.status', 'active')
            ->assertJsonPath('data.currency', 'LKR')
            ->assertJsonStructure(['data' => ['id', 'tenant_id', 'token', 'status', 'currency', 'subtotal', 'total_amount']]);
    }

    public function test_can_get_cart_by_token(): void
    {
        $create = $this->postJson('/api/v1/ecommerce/carts', [
            'tenant_id' => $this->tenantId,
            'currency' => 'LKR',
        ]);
        $token = $create->json('data.token');

        $response = $this->getJson("/api/v1/ecommerce/carts/{$token}?tenant_id={$this->tenantId}");

        $response->assertStatus(200)
            ->assertJsonPath('data.cart.token', $token);
    }

    public function test_can_add_item_to_cart(): void
    {
        $cart = $this->postJson('/api/v1/ecommerce/carts', [
            'tenant_id' => $this->tenantId,
            'currency' => 'LKR',
        ]);
        $token = $cart->json('data.token');

        $response = $this->postJson("/api/v1/ecommerce/carts/{$token}/items", [
            'tenant_id' => $this->tenantId,
            'product_id' => 1,
            'product_name' => 'Widget',
            'sku' => 'WID-001',
            'quantity' => '2',
            'unit_price' => '15.00',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.product_name', 'Widget')
            ->assertJsonPath('data.sku', 'WID-001')
            ->assertJsonPath('data.quantity', '2.0000')
            ->assertJsonPath('data.unit_price', '15.0000')
            ->assertJsonPath('data.line_total', '30.0000');
    }

    public function test_can_add_multiple_items_to_cart(): void
    {
        $cart = $this->postJson('/api/v1/ecommerce/carts', [
            'tenant_id' => $this->tenantId,
            'currency' => 'LKR',
        ]);
        $token = $cart->json('data.token');

        $this->postJson("/api/v1/ecommerce/carts/{$token}/items", [
            'tenant_id' => $this->tenantId,
            'product_id' => 1,
            'product_name' => 'Item A',
            'sku' => 'A-001',
            'quantity' => '1',
            'unit_price' => '10.00',
        ])->assertStatus(201);

        $this->postJson("/api/v1/ecommerce/carts/{$token}/items", [
            'tenant_id' => $this->tenantId,
            'product_id' => 2,
            'product_name' => 'Item B',
            'sku' => 'B-001',
            'quantity' => '3',
            'unit_price' => '5.00',
        ])->assertStatus(201);

        $cartResponse = $this->getJson("/api/v1/ecommerce/carts/{$token}?tenant_id={$this->tenantId}");
        $cartResponse->assertStatus(200);
        $this->assertCount(2, $cartResponse->json('data.items'));
    }

    public function test_can_remove_item_from_cart(): void
    {
        $cart = $this->postJson('/api/v1/ecommerce/carts', [
            'tenant_id' => $this->tenantId,
            'currency' => 'LKR',
        ]);
        $token = $cart->json('data.token');

        $itemResponse = $this->postJson("/api/v1/ecommerce/carts/{$token}/items", [
            'tenant_id' => $this->tenantId,
            'product_id' => 1,
            'product_name' => 'Removable',
            'sku' => 'REM-001',
            'quantity' => '1',
            'unit_price' => '20.00',
        ]);
        $itemId = $itemResponse->json('data.id');

        $this->deleteJson("/api/v1/ecommerce/carts/{$token}/items/{$itemId}?tenant_id={$this->tenantId}")
            ->assertStatus(200);

        $cartResponse = $this->getJson("/api/v1/ecommerce/carts/{$token}?tenant_id={$this->tenantId}");
        $this->assertCount(0, $cartResponse->json('data.items'));
    }

    public function test_cart_totals_calculated_correctly(): void
    {
        $cart = $this->postJson('/api/v1/ecommerce/carts', [
            'tenant_id' => $this->tenantId,
            'currency' => 'LKR',
        ]);
        $token = $cart->json('data.token');

        $this->postJson("/api/v1/ecommerce/carts/{$token}/items", [
            'tenant_id' => $this->tenantId,
            'product_id' => 1,
            'product_name' => 'Item X',
            'sku' => 'X-001',
            'quantity' => '2',
            'unit_price' => '50.00',
        ]);

        $this->postJson("/api/v1/ecommerce/carts/{$token}/items", [
            'tenant_id' => $this->tenantId,
            'product_id' => 2,
            'product_name' => 'Item Y',
            'sku' => 'Y-001',
            'quantity' => '1',
            'unit_price' => '30.00',
        ]);

        $cartResponse = $this->getJson("/api/v1/ecommerce/carts/{$token}?tenant_id={$this->tenantId}");
        // subtotal = (2 * 50) + (1 * 30) = 130
        $this->assertEquals('130.0000', $cartResponse->json('data.cart.subtotal'));
        $this->assertEquals('130.0000', $cartResponse->json('data.cart.total_amount'));
    }

    public function test_can_checkout_cart_creates_order(): void
    {
        $cart = $this->postJson('/api/v1/ecommerce/carts', [
            'tenant_id' => $this->tenantId,
            'currency' => 'LKR',
        ]);
        $token = $cart->json('data.token');

        $this->postJson("/api/v1/ecommerce/carts/{$token}/items", [
            'tenant_id' => $this->tenantId,
            'product_id' => 1,
            'product_name' => 'Checkout Item',
            'sku' => 'CHK-001',
            'quantity' => '2',
            'unit_price' => '100.00',
        ]);

        $response = $this->postJson("/api/v1/ecommerce/carts/{$token}/checkout", [
            'tenant_id' => $this->tenantId,
            'billing_name' => 'John Doe',
            'billing_email' => 'john@example.com',
            'billing_phone' => '+94771234567',
            'shipping_address' => '123 Main St, Colombo',
            'shipping_amount' => '10.00',
            'discount_amount' => '0.00',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.billing_name', 'John Doe')
            ->assertJsonPath('data.billing_email', 'john@example.com')
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.subtotal', '200.0000')
            ->assertJsonPath('data.shipping_amount', '10.0000')
            ->assertJsonPath('data.total_amount', '210.0000');

        $reference = $response->json('data.reference');
        $this->assertStringStartsWith('ECO-ORD-', $reference);
    }

    public function test_checkout_converts_cart_to_converted_status(): void
    {
        $cart = $this->postJson('/api/v1/ecommerce/carts', [
            'tenant_id' => $this->tenantId,
            'currency' => 'LKR',
        ]);
        $token = $cart->json('data.token');

        $this->postJson("/api/v1/ecommerce/carts/{$token}/items", [
            'tenant_id' => $this->tenantId,
            'product_id' => 1,
            'product_name' => 'Product',
            'sku' => 'PRD-001',
            'quantity' => '1',
            'unit_price' => '50.00',
        ]);

        $this->postJson("/api/v1/ecommerce/carts/{$token}/checkout", [
            'tenant_id' => $this->tenantId,
            'billing_name' => 'Jane Doe',
            'billing_email' => 'jane@example.com',
            'shipping_amount' => '0.00',
            'discount_amount' => '0.00',
        ])->assertStatus(201);

        $cartResponse = $this->getJson("/api/v1/ecommerce/carts/{$token}?tenant_id={$this->tenantId}");
        $cartResponse->assertJsonPath('data.cart.status', 'converted');
    }

    public function test_cannot_checkout_already_converted_cart(): void
    {
        $cart = $this->postJson('/api/v1/ecommerce/carts', [
            'tenant_id' => $this->tenantId,
            'currency' => 'LKR',
        ]);
        $token = $cart->json('data.token');

        $this->postJson("/api/v1/ecommerce/carts/{$token}/items", [
            'tenant_id' => $this->tenantId,
            'product_id' => 1,
            'product_name' => 'Product',
            'sku' => 'PRD-001',
            'quantity' => '1',
            'unit_price' => '50.00',
        ]);

        $checkoutPayload = [
            'tenant_id' => $this->tenantId,
            'billing_name' => 'Test User',
            'billing_email' => 'test@example.com',
            'shipping_amount' => '0.00',
            'discount_amount' => '0.00',
        ];

        $this->postJson("/api/v1/ecommerce/carts/{$token}/checkout", $checkoutPayload)->assertStatus(201);
        $this->postJson("/api/v1/ecommerce/carts/{$token}/checkout", $checkoutPayload)->assertStatus(422);
    }

    // ─────────────────────────────────────────────
    // StorefrontOrder tests
    // ─────────────────────────────────────────────

    private function createOrderViaCheckout(): array
    {
        $cart = $this->postJson('/api/v1/ecommerce/carts', [
            'tenant_id' => $this->tenantId,
            'currency' => 'LKR',
        ]);
        $token = $cart->json('data.token');

        $this->postJson("/api/v1/ecommerce/carts/{$token}/items", [
            'tenant_id' => $this->tenantId,
            'product_id' => 1,
            'product_name' => 'Order Item',
            'sku' => 'ORD-001',
            'quantity' => '1',
            'unit_price' => '100.00',
        ]);

        $orderResponse = $this->postJson("/api/v1/ecommerce/carts/{$token}/checkout", [
            'tenant_id' => $this->tenantId,
            'billing_name' => 'Test Customer',
            'billing_email' => 'customer@example.com',
            'shipping_amount' => '5.00',
            'discount_amount' => '0.00',
        ]);

        return [
            'id' => $orderResponse->json('data.id'),
            'reference' => $orderResponse->json('data.reference'),
        ];
    }

    public function test_can_list_storefront_orders(): void
    {
        $this->createOrderViaCheckout();

        $response = $this->getJson("/api/v1/ecommerce/orders?tenant_id={$this->tenantId}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data', 'meta' => ['total', 'current_page', 'last_page', 'per_page']]);

        $this->assertGreaterThanOrEqual(1, count($response->json('data')));
    }

    public function test_can_get_storefront_order_by_id(): void
    {
        $order = $this->createOrderViaCheckout();

        $response = $this->getJson("/api/v1/ecommerce/orders/{$order['id']}?tenant_id={$this->tenantId}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $order['id'])
            ->assertJsonPath('data.reference', $order['reference']);
    }

    public function test_can_update_storefront_order_status(): void
    {
        $order = $this->createOrderViaCheckout();

        $response = $this->putJson("/api/v1/ecommerce/orders/{$order['id']}/status", [
            'tenant_id' => $this->tenantId,
            'status' => 'confirmed',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'confirmed');
    }

    public function test_can_cancel_pending_order(): void
    {
        $order = $this->createOrderViaCheckout();

        $response = $this->postJson("/api/v1/ecommerce/orders/{$order['id']}/cancel?tenant_id={$this->tenantId}");

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'cancelled');
    }

    public function test_can_get_order_lines(): void
    {
        $order = $this->createOrderViaCheckout();

        $response = $this->getJson("/api/v1/ecommerce/orders/{$order['id']}/lines?tenant_id={$this->tenantId}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertGreaterThanOrEqual(1, count($response->json('data')));
    }

    public function test_can_delete_storefront_order(): void
    {
        $order = $this->createOrderViaCheckout();

        $this->deleteJson("/api/v1/ecommerce/orders/{$order['id']}?tenant_id={$this->tenantId}")
            ->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->getJson("/api/v1/ecommerce/orders/{$order['id']}?tenant_id={$this->tenantId}")
            ->assertStatus(404);
    }
}
