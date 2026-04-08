<?php

namespace Tests\Feature\Order;

use App\Domain\Order\Enums\OrderStatus;
use App\Infrastructure\Persistence\Eloquent\OrderModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * OrderApiTest — full HTTP stack integration tests.
 *
 * Uses RefreshDatabase to reset state between tests.
 * Tests the entire pipeline: HTTP → Controller → Handler → Domain → Repository → DB.
 */
class OrderApiTest extends TestCase
{
    use RefreshDatabase;

    private const CUSTOMER_UUID = '550e8400-e29b-41d4-a716-446655440000';

    // -------------------------------------------------------------------------
    // POST /api/orders — Place Order
    // -------------------------------------------------------------------------

    /** @test */
    public function it_places_a_new_order_successfully(): void
    {
        $response = $this->postJson('/api/orders', [
            'customer_id' => self::CUSTOMER_UUID,
        ]);

        $response->assertCreated()
                 ->assertJsonStructure(['message', 'order_id'])
                 ->assertJsonFragment(['message' => 'Order placed successfully.']);

        $orderId = $response->json('order_id');

        $this->assertDatabaseHas('orders', [
            'id'          => $orderId,
            'customer_id' => self::CUSTOMER_UUID,
            'status'      => OrderStatus::Pending->value,
        ]);
    }

    /** @test */
    public function it_rejects_a_missing_customer_id(): void
    {
        $response = $this->postJson('/api/orders', []);

        $response->assertUnprocessable()
                 ->assertJsonValidationErrors(['customer_id']);
    }

    /** @test */
    public function it_rejects_a_non_uuid_customer_id(): void
    {
        $response = $this->postJson('/api/orders', [
            'customer_id' => 'not-a-uuid',
        ]);

        $response->assertUnprocessable()
                 ->assertJsonValidationErrors(['customer_id']);
    }

    // -------------------------------------------------------------------------
    // GET /api/orders/{id} — Show Order
    // -------------------------------------------------------------------------

    /** @test */
    public function it_returns_an_order_by_id(): void
    {
        // Arrange: place an order via the API to get a real ID
        $placeResponse = $this->postJson('/api/orders', [
            'customer_id' => self::CUSTOMER_UUID,
        ]);

        $orderId = $placeResponse->json('order_id');

        // Act
        $response = $this->getJson("/api/orders/{$orderId}");

        // Assert
        $response->assertOk()
                 ->assertJsonStructure([
                     'data' => [
                         'id',
                         'customer_id',
                         'status' => ['value', 'label'],
                         'total'  => ['amount_cents', 'currency', 'formatted'],
                         'placed_at',
                     ],
                 ])
                 ->assertJsonPath('data.id', $orderId)
                 ->assertJsonPath('data.customer_id', self::CUSTOMER_UUID)
                 ->assertJsonPath('data.status.value', OrderStatus::Pending->value);
    }

    /** @test */
    public function it_returns_404_for_unknown_order_id(): void
    {
        $fakeId = '00000000-0000-0000-0000-000000000000';

        $this->getJson("/api/orders/{$fakeId}")
             ->assertNotFound()
             ->assertJsonStructure(['error']);
    }

    // -------------------------------------------------------------------------
    // POST /api/orders/{id}/cancel — Cancel Order
    // -------------------------------------------------------------------------

    /** @test */
    public function it_cancels_a_pending_order(): void
    {
        $orderId = $this->postJson('/api/orders', [
            'customer_id' => self::CUSTOMER_UUID,
        ])->json('order_id');

        $response = $this->postJson("/api/orders/{$orderId}/cancel", [
            'reason' => 'Customer requested cancellation.',
        ]);

        $response->assertOk()
                 ->assertJsonFragment(['message' => 'Order cancelled successfully.']);

        $this->assertDatabaseHas('orders', [
            'id'     => $orderId,
            'status' => OrderStatus::Cancelled->value,
        ]);
    }

    /** @test */
    public function it_returns_422_when_cancelling_an_already_cancelled_order(): void
    {
        $orderId = $this->postJson('/api/orders', [
            'customer_id' => self::CUSTOMER_UUID,
        ])->json('order_id');

        // First cancellation — should succeed
        $this->postJson("/api/orders/{$orderId}/cancel", ['reason' => 'First.'])
             ->assertOk();

        // Second cancellation — should return 422 (domain rule violation)
        $this->postJson("/api/orders/{$orderId}/cancel", ['reason' => 'Second.'])
             ->assertStatus(422)
             ->assertJsonStructure(['error']);
    }

    /** @test */
    public function it_returns_404_when_cancelling_a_nonexistent_order(): void
    {
        $fakeId = '00000000-0000-0000-0000-000000000000';

        $this->postJson("/api/orders/{$fakeId}/cancel", ['reason' => 'test'])
             ->assertNotFound();
    }

    // -------------------------------------------------------------------------
    // Full lifecycle walkthrough
    // -------------------------------------------------------------------------

    /** @test */
    public function full_order_lifecycle_place_then_show(): void
    {
        // 1. Place
        $placeResponse = $this->postJson('/api/orders', [
            'customer_id' => self::CUSTOMER_UUID,
        ]);
        $placeResponse->assertCreated();
        $orderId = $placeResponse->json('order_id');

        // 2. Show — verify pending status
        $this->getJson("/api/orders/{$orderId}")
             ->assertOk()
             ->assertJsonPath('data.status.value', 'pending');

        // 3. Cancel
        $this->postJson("/api/orders/{$orderId}/cancel", [
            'reason' => 'Integration test cancellation.',
        ])->assertOk();

        // 4. Show again — verify cancelled status
        $this->getJson("/api/orders/{$orderId}")
             ->assertOk()
             ->assertJsonPath('data.status.value', 'cancelled');
    }
}
