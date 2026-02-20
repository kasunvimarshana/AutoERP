<?php

namespace Tests\Feature;

use App\Enums\InvoiceStatus;
use App\Enums\TenantStatus;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PaymentCrudTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create(['status' => TenantStatus::Active]);
        $this->admin = User::factory()->create(['tenant_id' => $this->tenant->id]);

        $payPerm = Permission::firstOrCreate(['name' => 'payments.create', 'guard_name' => 'api']);
        $invPerm = Permission::firstOrCreate(['name' => 'invoices.create', 'guard_name' => 'api']);
        $sendPerm = Permission::firstOrCreate(['name' => 'invoices.send', 'guard_name' => 'api']);

        $this->admin->givePermissionTo([$payPerm, $invPerm, $sendPerm]);
    }

    public function test_admin_can_record_standalone_payment(): void
    {
        $response = $this->actingAs($this->admin, 'api')
            ->postJson('/api/v1/payments', [
                'method' => 'cash',
                'amount' => '150.00',
                'currency' => 'USD',
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure(['data' => ['id', 'payment_number', 'status', 'amount', 'net_amount']]);

        $this->assertEquals(150.0, (float) $response->json('data.amount'));
    }

    public function test_payment_linked_to_invoice_updates_invoice_status(): void
    {
        // Create an invoice first
        $invoiceResponse = $this->actingAs($this->admin, 'api')
            ->postJson('/api/v1/invoices', [
                'currency' => 'USD',
                'items' => [
                    [
                        'description' => 'Service',
                        'quantity' => '1',
                        'unit_price' => '100.00',
                    ],
                ],
            ]);

        $invoiceId = $invoiceResponse->json('data.id');
        $invoiceTotal = (float) $invoiceResponse->json('data.total');

        // Send invoice first (to allow payment)
        $this->actingAs($this->admin, 'api')
            ->patchJson("/api/v1/invoices/{$invoiceId}/send");

        // Record full payment against invoice
        $this->actingAs($this->admin, 'api')
            ->postJson('/api/v1/payments', [
                'invoice_id' => $invoiceId,
                'method' => 'bank',
                'amount' => (string) $invoiceTotal,
                'currency' => 'USD',
            ])
            ->assertStatus(201);

        // Invoice should now be marked as Paid
        $this->actingAs($this->admin, 'api')
            ->getJson('/api/v1/invoices')
            ->assertJsonFragment(['status' => InvoiceStatus::Paid->value]);
    }

    public function test_partial_payment_marks_invoice_as_partial(): void
    {
        $invoiceResponse = $this->actingAs($this->admin, 'api')
            ->postJson('/api/v1/invoices', [
                'currency' => 'USD',
                'items' => [
                    ['description' => 'Item', 'quantity' => '1', 'unit_price' => '200.00'],
                ],
            ]);

        $invoiceId = $invoiceResponse->json('data.id');

        // Partial payment (50 of 200)
        $this->actingAs($this->admin, 'api')
            ->postJson('/api/v1/payments', [
                'invoice_id' => $invoiceId,
                'method' => 'card',
                'amount' => '50.00',
                'currency' => 'USD',
            ])
            ->assertStatus(201);

        // Invoice should be partial
        $this->actingAs($this->admin, 'api')
            ->getJson('/api/v1/invoices')
            ->assertJsonFragment(['status' => InvoiceStatus::Partial->value]);
    }

    public function test_payment_method_is_required(): void
    {
        $this->actingAs($this->admin, 'api')
            ->postJson('/api/v1/payments', [
                'amount' => '100.00',
                'currency' => 'USD',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['method']);
    }

    public function test_payment_amount_must_be_positive(): void
    {
        $this->actingAs($this->admin, 'api')
            ->postJson('/api/v1/payments', [
                'method' => 'cash',
                'amount' => '0',
                'currency' => 'USD',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['amount']);
    }

    public function test_fee_is_deducted_from_net_amount(): void
    {
        $response = $this->actingAs($this->admin, 'api')
            ->postJson('/api/v1/payments', [
                'method' => 'card',
                'amount' => '100.00',
                'fee_amount' => '2.50',
                'currency' => 'USD',
            ]);

        $response->assertStatus(201);
        // net_amount = 100 - 2.50 = 97.50
        $this->assertEquals(97.5, (float) $response->json('data.net_amount'));
    }
}
