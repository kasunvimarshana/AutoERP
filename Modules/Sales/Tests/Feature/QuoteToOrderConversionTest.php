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
 * Quote to Order Conversion Tests
 *
 * Tests for quote approval and conversion to sales order
 */
class QuoteToOrderConversionTest extends TestCase
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
                'quote.view',
                'quote.create',
                'quote.update',
                'quote.approve',
                'quote.convert',
                'order.create',
            ],
        ]);

        $this->customer = Customer::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $this->product = Product::factory()->create([
            'tenant_id' => $this->tenant->id,
            'unit_price' => 100.00,
        ]);
    }

    public function test_it_creates_draft_quote(): void
    {
        $quoteData = [
            'customer_id' => $this->customer->id,
            'status' => 'draft',
            'valid_until' => now()->addDays(30)->toDateString(),
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 10,
                    'unit_price' => 100.00,
                ],
            ],
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/sales/quotes', $quoteData);

        $response->assertStatus(201)
            ->assertJsonPath('data.status', 'draft')
            ->assertJsonPath('data.customer_id', $this->customer->id);

        $this->assertDatabaseHas('quotes', [
            'customer_id' => $this->customer->id,
            'status' => 'draft',
            'tenant_id' => $this->tenant->id,
        ]);
    }

    public function test_it_sends_quote_to_customer(): void
    {
        $quote = Quote::factory()->create([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $this->customer->id,
            'status' => 'draft',
        ]);

        $response = $this->actingAs($this->user)
            ->putJson("/api/v1/sales/quotes/{$quote->id}/send");

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'sent');

        $this->assertDatabaseHas('quotes', [
            'id' => $quote->id,
            'status' => 'sent',
        ]);

        // Check that sent_at timestamp is set
        $quote->refresh();
        $this->assertNotNull($quote->sent_at);
    }

    public function test_it_approves_quote(): void
    {
        $quote = Quote::factory()->create([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $this->customer->id,
            'status' => 'sent',
        ]);

        $response = $this->actingAs($this->user)
            ->putJson("/api/v1/sales/quotes/{$quote->id}/approve");

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'approved');

        $this->assertDatabaseHas('quotes', [
            'id' => $quote->id,
            'status' => 'approved',
        ]);
    }

    public function test_it_rejects_quote(): void
    {
        $quote = Quote::factory()->create([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $this->customer->id,
            'status' => 'sent',
        ]);

        $rejectData = [
            'reason' => 'Customer declined pricing',
        ];

        $response = $this->actingAs($this->user)
            ->putJson("/api/v1/sales/quotes/{$quote->id}/reject", $rejectData);

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'rejected');

        $this->assertDatabaseHas('quotes', [
            'id' => $quote->id,
            'status' => 'rejected',
        ]);
    }

    public function test_it_converts_approved_quote_to_order(): void
    {
        $quote = Quote::factory()->create([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $this->customer->id,
            'status' => 'approved',
            'total' => 1000.00,
        ]);

        $quote->lines()->create([
            'product_id' => $this->product->id,
            'quantity' => 10,
            'unit_price' => 100.00,
            'total' => 1000.00,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/sales/quotes/{$quote->id}/convert-to-order");

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'order_number',
                    'customer_id',
                    'status',
                    'total',
                ],
            ]);

        // Check order was created
        $this->assertDatabaseHas('sales_orders', [
            'customer_id' => $this->customer->id,
            'quote_id' => $quote->id,
            'status' => 'draft',
        ]);

        // Check quote status updated
        $quote->refresh();
        $this->assertEquals('converted', $quote->status);
    }

    public function test_it_copies_quote_lines_to_order(): void
    {
        $quote = Quote::factory()->create([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $this->customer->id,
            'status' => 'approved',
        ]);

        $quote->lines()->create([
            'product_id' => $this->product->id,
            'quantity' => 10,
            'unit_price' => 100.00,
            'discount_percentage' => 5,
            'total' => 950.00,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/sales/quotes/{$quote->id}/convert-to-order");

        $response->assertStatus(201);

        $orderId = $response->json('data.id');
        $order = SalesOrder::find($orderId);

        $this->assertCount(1, $order->lines);

        $orderLine = $order->lines->first();
        $quoteLine = $quote->lines->first();

        $this->assertEquals($quoteLine->product_id, $orderLine->product_id);
        $this->assertEquals($quoteLine->quantity, $orderLine->quantity);
        $this->assertEquals($quoteLine->unit_price, $orderLine->unit_price);
        $this->assertEquals($quoteLine->discount_percentage, $orderLine->discount_percentage);
    }

    public function test_it_prevents_converting_non_approved_quote(): void
    {
        $quote = Quote::factory()->create([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $this->customer->id,
            'status' => 'draft',
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/sales/quotes/{$quote->id}/convert-to-order");

        $response->assertStatus(422)
            ->assertJsonFragment(['message' => 'Only approved quotes can be converted']);
    }

    public function test_it_prevents_converting_expired_quote(): void
    {
        $quote = Quote::factory()->create([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $this->customer->id,
            'status' => 'approved',
            'valid_until' => now()->subDays(1), // Expired yesterday
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/sales/quotes/{$quote->id}/convert-to-order");

        $response->assertStatus(422)
            ->assertJsonFragment(['message' => 'Quote has expired']);
    }

    public function test_it_prevents_converting_already_converted_quote(): void
    {
        $quote = Quote::factory()->create([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $this->customer->id,
            'status' => 'converted',
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/sales/quotes/{$quote->id}/convert-to-order");

        $response->assertStatus(422)
            ->assertJsonFragment(['message' => 'Quote already converted']);
    }

    public function test_it_calculates_quote_totals_correctly(): void
    {
        $quoteData = [
            'customer_id' => $this->customer->id,
            'status' => 'draft',
            'valid_until' => now()->addDays(30)->toDateString(),
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 10,
                    'unit_price' => 100.00,
                    'discount_percentage' => 10,
                ],
            ],
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/sales/quotes', $quoteData);

        $response->assertStatus(201);

        $subtotal = 10 * 100.00; // 1000.00
        $discount = $subtotal * 0.10; // 100.00
        $expectedTotal = $subtotal - $discount; // 900.00

        $response->assertJsonPath('data.subtotal', 1000.00)
            ->assertJsonPath('data.discount_amount', 100.00)
            ->assertJsonPath('data.total', 900.00);
    }

    public function test_it_revises_quote(): void
    {
        $quote = Quote::factory()->create([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $this->customer->id,
            'status' => 'sent',
            'revision' => 1,
        ]);

        $reviseData = [
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 15,
                    'unit_price' => 95.00,
                ],
            ],
        ];

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/sales/quotes/{$quote->id}/revise", $reviseData);

        $response->assertStatus(201)
            ->assertJsonPath('data.revision', 2)
            ->assertJsonPath('data.status', 'draft');

        // Original quote should be superseded
        $quote->refresh();
        $this->assertEquals('superseded', $quote->status);
    }

    public function test_it_tracks_quote_history(): void
    {
        $quote = Quote::factory()->create([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $this->customer->id,
            'status' => 'draft',
        ]);

        // Send quote
        $this->actingAs($this->user)
            ->putJson("/api/v1/sales/quotes/{$quote->id}/send");

        // Approve quote
        $this->actingAs($this->user)
            ->putJson("/api/v1/sales/quotes/{$quote->id}/approve");

        // Get history
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/sales/quotes/{$quote->id}/history");

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

        $history = $response->json('data');
        $this->assertGreaterThanOrEqual(2, count($history));
    }

    public function test_it_applies_quote_level_discount(): void
    {
        $quoteData = [
            'customer_id' => $this->customer->id,
            'status' => 'draft',
            'valid_until' => now()->addDays(30)->toDateString(),
            'discount_percentage' => 5, // 5% quote-level discount
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 10,
                    'unit_price' => 100.00,
                ],
            ],
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/sales/quotes', $quoteData);

        $response->assertStatus(201);

        $subtotal = 1000.00;
        $discount = $subtotal * 0.05; // 50.00
        $expectedTotal = $subtotal - $discount; // 950.00

        $response->assertJsonPath('data.subtotal', 1000.00)
            ->assertJsonPath('data.discount_amount', 50.00)
            ->assertJsonPath('data.total', 950.00);
    }

    public function test_it_prevents_cross_tenant_quote_conversion(): void
    {
        // Create quote for tenant1
        $quote = Quote::factory()->create([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $this->customer->id,
            'status' => 'approved',
        ]);

        // Create different tenant and user
        $tenant2 = Tenant::factory()->create();
        tenancy()->initialize($tenant2);

        $user2 = User::factory()->create([
            'tenant_id' => $tenant2->id,
            'permissions' => ['quote.convert'],
        ]);

        // Try to convert tenant1's quote from tenant2
        $response = $this->actingAs($user2)
            ->postJson("/api/v1/sales/quotes/{$quote->id}/convert-to-order");

        $response->assertStatus(404);
    }

    public function test_it_sends_notification_on_quote_approval(): void
    {
        $quote = Quote::factory()->create([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $this->customer->id,
            'status' => 'sent',
        ]);

        $response = $this->actingAs($this->user)
            ->putJson("/api/v1/sales/quotes/{$quote->id}/approve");

        $response->assertStatus(200);

        // Check that notification/email was queued (if implemented)
        // This would check the queue or notifications table
        $this->assertDatabaseHas('notifications', [
            'type' => 'QuoteApproved',
            'notifiable_id' => $this->customer->id,
        ]);
    }
}
