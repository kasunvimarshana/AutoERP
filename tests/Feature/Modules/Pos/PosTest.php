<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Pos;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PosTest extends TestCase
{
    use RefreshDatabase;

    private int $tenantId;

    private int $userId = 1;

    protected function setUp(): void
    {
        parent::setUp();

        $tenantResponse = $this->postJson('/api/v1/tenants', [
            'name' => 'POS Test Tenant',
            'slug' => 'pos-test-tenant',
        ]);
        $this->tenantId = $tenantResponse->json('data.id');
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    private function openSession(array $overrides = []): array
    {
        return $this->postJson('/api/v1/pos/sessions', array_merge([
            'tenant_id' => $this->tenantId,
            'user_id' => $this->userId,
            'opening_float' => '100.00',
            'currency' => 'USD',
            'notes' => 'Morning shift',
        ], $overrides))->json('data');
    }

    private function createOrder(int $sessionId, array $overrides = []): array
    {
        return $this->postJson('/api/v1/pos/orders', array_merge([
            'tenant_id' => $this->tenantId,
            'pos_session_id' => $sessionId,
            'currency' => 'USD',
            'lines' => [
                [
                    'product_id' => 1,
                    'product_name' => 'Widget A',
                    'sku' => 'WGT-A',
                    'quantity' => '2',
                    'unit_price' => '10.00',
                    'discount_amount' => '0',
                    'tax_amount' => '2.00',
                ],
            ],
            'notes' => null,
        ], $overrides))->json('data');
    }

    // ─── Session Tests ────────────────────────────────────────────────────────

    public function test_can_open_pos_session(): void
    {
        $response = $this->postJson('/api/v1/pos/sessions', [
            'tenant_id' => $this->tenantId,
            'user_id' => $this->userId,
            'opening_float' => '150.00',
            'currency' => 'USD',
            'notes' => 'Opening shift',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.tenant_id', $this->tenantId)
            ->assertJsonPath('data.status', 'open')
            ->assertJsonPath('data.currency', 'USD')
            ->assertJsonStructure(['data' => [
                'id', 'tenant_id', 'user_id', 'reference', 'status',
                'opened_at', 'closed_at', 'currency', 'opening_float',
                'closing_float', 'total_sales', 'total_refunds',
                'notes', 'created_at', 'updated_at',
            ]]);
    }

    public function test_cannot_open_second_session_while_one_is_active(): void
    {
        $this->openSession();

        $response = $this->postJson('/api/v1/pos/sessions', [
            'tenant_id' => $this->tenantId,
            'user_id' => $this->userId,
            'opening_float' => '100.00',
            'currency' => 'USD',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_can_close_pos_session(): void
    {
        $session = $this->openSession();

        $response = $this->putJson("/api/v1/pos/sessions/{$session['id']}/close", [
            'tenant_id' => $this->tenantId,
            'closing_float' => '250.00',
            'notes' => 'End of shift',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'closed')
            ->assertJsonPath('data.id', $session['id']);

        $this->assertNotNull($response->json('data.closed_at'));
    }

    public function test_can_list_pos_sessions(): void
    {
        $this->openSession(['user_id' => 1]);
        // Each user can have one active session; open a second session for a different user
        $s2 = $this->openSession(['user_id' => 2]);

        $response = $this->getJson("/api/v1/pos/sessions?tenant_id={$this->tenantId}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('meta.total', 2);
    }

    public function test_can_get_pos_session_by_id(): void
    {
        $session = $this->openSession();

        $response = $this->getJson("/api/v1/pos/sessions/{$session['id']}?tenant_id={$this->tenantId}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $session['id'])
            ->assertJsonPath('data.reference', $session['reference']);
    }

    public function test_returns_404_for_missing_session(): void
    {
        $response = $this->getJson("/api/v1/pos/sessions/99999?tenant_id={$this->tenantId}");

        $response->assertStatus(404)
            ->assertJsonPath('success', false);
    }

    public function test_can_delete_pos_session(): void
    {
        $session = $this->openSession();

        $this->deleteJson("/api/v1/pos/sessions/{$session['id']}?tenant_id={$this->tenantId}")
            ->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->getJson("/api/v1/pos/sessions/{$session['id']}?tenant_id={$this->tenantId}")
            ->assertStatus(404);
    }

    // ─── Order Tests ──────────────────────────────────────────────────────────

    public function test_can_create_pos_order(): void
    {
        $session = $this->openSession();

        $response = $this->postJson('/api/v1/pos/orders', [
            'tenant_id' => $this->tenantId,
            'pos_session_id' => $session['id'],
            'currency' => 'USD',
            'lines' => [
                [
                    'product_id' => 1,
                    'product_name' => 'Widget A',
                    'sku' => 'WGT-A',
                    'quantity' => '2',
                    'unit_price' => '10.00',
                    'discount_amount' => '0',
                    'tax_amount' => '2.00',
                ],
            ],
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'draft')
            ->assertJsonPath('data.currency', 'USD')
            ->assertJsonStructure(['data' => [
                'id', 'tenant_id', 'pos_session_id', 'reference', 'status',
                'currency', 'subtotal', 'tax_amount', 'discount_amount',
                'total_amount', 'paid_amount', 'change_amount',
            ]]);
    }

    public function test_pos_order_calculates_totals_correctly(): void
    {
        $session = $this->openSession();

        $response = $this->postJson('/api/v1/pos/orders', [
            'tenant_id' => $this->tenantId,
            'pos_session_id' => $session['id'],
            'currency' => 'USD',
            'lines' => [
                [
                    'product_id' => 1,
                    'product_name' => 'Item A',
                    'sku' => 'SKU-A',
                    'quantity' => '3',
                    'unit_price' => '20.00',
                    'discount_amount' => '5.00',
                    'tax_amount' => '3.00',
                ],
                [
                    'product_id' => 2,
                    'product_name' => 'Item B',
                    'sku' => 'SKU-B',
                    'quantity' => '1',
                    'unit_price' => '15.00',
                    'discount_amount' => '0',
                    'tax_amount' => '1.50',
                ],
            ],
        ]);

        $response->assertStatus(201);
        $data = $response->json('data');

        // subtotal = 3*20 + 1*15 = 60 + 15 = 75
        $this->assertEquals('75.0000', $data['subtotal']);
        // tax = 3.00 + 1.50 = 4.50
        $this->assertEquals('4.5000', $data['tax_amount']);
        // discount = 5.00
        $this->assertEquals('5.0000', $data['discount_amount']);
        // total = subtotal + tax - discount = 75 + 4.50 - 5 = 74.50
        $this->assertEquals('74.5000', $data['total_amount']);
    }

    public function test_can_get_pos_order_by_id(): void
    {
        $session = $this->openSession();
        $order = $this->createOrder($session['id']);

        $response = $this->getJson("/api/v1/pos/orders/{$order['id']}?tenant_id={$this->tenantId}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $order['id'])
            ->assertJsonPath('data.reference', $order['reference']);
    }

    public function test_can_list_pos_orders(): void
    {
        $session = $this->openSession();
        $this->createOrder($session['id']);
        $this->createOrder($session['id']);

        $response = $this->getJson("/api/v1/pos/orders?tenant_id={$this->tenantId}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('meta.total', 2);
    }

    public function test_can_pay_pos_order_with_cash(): void
    {
        $session = $this->openSession();
        $order = $this->createOrder($session['id']);

        $response = $this->postJson("/api/v1/pos/orders/{$order['id']}/pay", [
            'tenant_id' => $this->tenantId,
            'payments' => [
                ['method' => 'cash', 'amount' => '25.00', 'currency' => 'USD'],
            ],
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'paid');
    }

    public function test_can_pay_pos_order_with_card(): void
    {
        $session = $this->openSession();
        $order = $this->createOrder($session['id']);

        $response = $this->postJson("/api/v1/pos/orders/{$order['id']}/pay", [
            'tenant_id' => $this->tenantId,
            'payments' => [
                ['method' => 'card', 'amount' => '25.00', 'currency' => 'USD', 'reference' => 'TXN-12345'],
            ],
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'paid');
    }

    public function test_cannot_pay_already_paid_order(): void
    {
        $session = $this->openSession();
        $order = $this->createOrder($session['id']);

        $this->postJson("/api/v1/pos/orders/{$order['id']}/pay", [
            'tenant_id' => $this->tenantId,
            'payments' => [
                ['method' => 'cash', 'amount' => '25.00', 'currency' => 'USD'],
            ],
        ])->assertStatus(200);

        $response = $this->postJson("/api/v1/pos/orders/{$order['id']}/pay", [
            'tenant_id' => $this->tenantId,
            'payments' => [
                ['method' => 'cash', 'amount' => '25.00', 'currency' => 'USD'],
            ],
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_can_cancel_draft_pos_order(): void
    {
        $session = $this->openSession();
        $order = $this->createOrder($session['id']);

        $response = $this->postJson("/api/v1/pos/orders/{$order['id']}/cancel?tenant_id={$this->tenantId}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'cancelled');
    }

    public function test_cannot_cancel_paid_order(): void
    {
        $session = $this->openSession();
        $order = $this->createOrder($session['id']);

        $this->postJson("/api/v1/pos/orders/{$order['id']}/pay", [
            'tenant_id' => $this->tenantId,
            'payments' => [['method' => 'cash', 'amount' => '25.00', 'currency' => 'USD']],
        ])->assertStatus(200);

        $response = $this->postJson("/api/v1/pos/orders/{$order['id']}/cancel?tenant_id={$this->tenantId}");

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_can_refund_paid_pos_order(): void
    {
        $session = $this->openSession();
        $order = $this->createOrder($session['id']);

        $this->postJson("/api/v1/pos/orders/{$order['id']}/pay", [
            'tenant_id' => $this->tenantId,
            'payments' => [['method' => 'cash', 'amount' => '25.00', 'currency' => 'USD']],
        ])->assertStatus(200);

        $response = $this->postJson("/api/v1/pos/orders/{$order['id']}/refund", [
            'tenant_id' => $this->tenantId,
            'refund_amount' => '22.0000',
            'method' => 'cash',
            'notes' => 'Customer returned item',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'refunded');
    }

    public function test_can_get_order_lines(): void
    {
        $session = $this->openSession();
        $order = $this->createOrder($session['id']);

        $response = $this->getJson("/api/v1/pos/orders/{$order['id']}/lines?tenant_id={$this->tenantId}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data')
            ->assertJsonStructure(['data' => [['id', 'product_id', 'product_name', 'sku', 'quantity', 'unit_price', 'line_total']]]);
    }

    public function test_can_get_order_payments(): void
    {
        $session = $this->openSession();
        $order = $this->createOrder($session['id']);

        $this->postJson("/api/v1/pos/orders/{$order['id']}/pay", [
            'tenant_id' => $this->tenantId,
            'payments' => [
                ['method' => 'cash', 'amount' => '10.00', 'currency' => 'USD'],
                ['method' => 'card', 'amount' => '12.00', 'currency' => 'USD'],
            ],
        ])->assertStatus(200);

        $response = $this->getJson("/api/v1/pos/orders/{$order['id']}/payments?tenant_id={$this->tenantId}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(2, 'data');
    }

    public function test_can_delete_pos_order(): void
    {
        $session = $this->openSession();
        $order = $this->createOrder($session['id']);

        $this->deleteJson("/api/v1/pos/orders/{$order['id']}?tenant_id={$this->tenantId}")
            ->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->getJson("/api/v1/pos/orders/{$order['id']}?tenant_id={$this->tenantId}")
            ->assertStatus(404);
    }
}
