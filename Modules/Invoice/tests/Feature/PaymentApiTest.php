<?php

declare(strict_types=1);

namespace Modules\Invoice\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Customer\Models\Customer;
use Modules\Customer\Models\Vehicle;
use Modules\Invoice\Models\Invoice;
use Modules\Invoice\Models\Payment;
use Modules\Organization\Models\Branch;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Payment API Feature Test
 *
 * Tests Payment API endpoints and scenarios
 */
class PaymentApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Customer $customer;

    private Vehicle $vehicle;

    private Branch $branch;

    private Invoice $invoice;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => 'user']);
        $this->user = User::factory()->create();
        $this->user->assignRole('user');

        $this->customer = Customer::factory()->create();
        $this->vehicle = Vehicle::factory()->create(['customer_id' => $this->customer->id]);
        $this->branch = Branch::factory()->create();

        $this->invoice = Invoice::factory()->create([
            'customer_id' => $this->customer->id,
            'vehicle_id' => $this->vehicle->id,
            'branch_id' => $this->branch->id,
            'total_amount' => 1000,
            'amount_paid' => 0,
            'balance' => 1000,
            'status' => 'pending',
        ]);
    }

    /**
     * Test can record full payment
     */
    public function test_can_record_full_payment(): void
    {
        $data = [
            'invoice_id' => $this->invoice->id,
            'amount' => 1000,
            'payment_method' => 'cash',
            'payment_date' => now()->toDateString(),
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/payments', $data);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'payment_number',
                    'amount',
                ],
            ]);

        $this->assertDatabaseHas('payments', [
            'invoice_id' => $this->invoice->id,
            'amount' => 1000,
        ]);

        $this->invoice->refresh();
        $this->assertEquals(1000, $this->invoice->amount_paid);
        $this->assertEquals(0, $this->invoice->balance);
        $this->assertEquals('paid', $this->invoice->status);
    }

    /**
     * Test can record partial payment
     */
    public function test_can_record_partial_payment(): void
    {
        $data = [
            'invoice_id' => $this->invoice->id,
            'amount' => 500,
            'payment_method' => 'credit_card',
            'payment_date' => now()->toDateString(),
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/payments', $data);

        $response->assertStatus(201);

        $this->invoice->refresh();
        $this->assertEquals(500, $this->invoice->amount_paid);
        $this->assertEquals(500, $this->invoice->balance);
        $this->assertEquals('partial', $this->invoice->status);
    }

    /**
     * Test cannot record payment exceeding balance
     */
    public function test_cannot_record_payment_exceeding_balance(): void
    {
        $data = [
            'invoice_id' => $this->invoice->id,
            'amount' => 1500,
            'payment_method' => 'cash',
            'payment_date' => now()->toDateString(),
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/payments', $data);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['amount']);
    }

    /**
     * Test can void payment
     */
    public function test_can_void_payment(): void
    {
        $payment = Payment::factory()->create([
            'invoice_id' => $this->invoice->id,
            'amount' => 500,
            'status' => 'completed',
        ]);

        $this->invoice->update([
            'amount_paid' => 500,
            'balance' => 500,
            'status' => 'partial',
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/payments/{$payment->id}/void", [
                'notes' => 'Payment error',
            ]);

        $response->assertStatus(200);

        $payment->refresh();
        $this->assertEquals('voided', $payment->status);

        $this->invoice->refresh();
        $this->assertEquals(0, $this->invoice->amount_paid);
        $this->assertEquals(1000, $this->invoice->balance);
    }

    /**
     * Test can get payment history
     */
    public function test_can_get_payment_history(): void
    {
        Payment::factory()->count(3)->create([
            'invoice_id' => $this->invoice->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/payments');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data',
            ]);
    }

    /**
     * Test can get payment history for specific invoice
     */
    public function test_can_get_payment_history_for_invoice(): void
    {
        Payment::factory()->count(2)->create([
            'invoice_id' => $this->invoice->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/payments/invoice/{$this->invoice->id}/history");

        $response->assertStatus(200);
    }
}
