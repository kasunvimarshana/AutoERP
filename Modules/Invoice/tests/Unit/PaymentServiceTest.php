<?php

declare(strict_types=1);

namespace Modules\Invoice\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Customer\Models\Customer;
use Modules\Customer\Models\Vehicle;
use Modules\Invoice\Models\Invoice;
use Modules\Invoice\Models\Payment;
use Modules\Invoice\Services\PaymentService;
use Modules\Organization\Models\Branch;
use Tests\TestCase;

/**
 * Payment Service Unit Test
 *
 * Tests PaymentService business logic
 */
class PaymentServiceTest extends TestCase
{
    use RefreshDatabase;

    private PaymentService $paymentService;

    private Customer $customer;

    private Vehicle $vehicle;

    private Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();

        $this->paymentService = app(PaymentService::class);
        $this->customer = Customer::factory()->create();
        $this->vehicle = Vehicle::factory()->create(['customer_id' => $this->customer->id]);
        $this->branch = Branch::factory()->create();
    }

    /**
     * Test payment updates invoice balance
     */
    public function test_payment_updates_invoice_balance(): void
    {
        $invoice = Invoice::factory()->create([
            'customer_id' => $this->customer->id,
            'vehicle_id' => $this->vehicle->id,
            'branch_id' => $this->branch->id,
            'total_amount' => 1000,
            'amount_paid' => 0,
            'balance' => 1000,
        ]);

        $this->paymentService->recordPayment([
            'invoice_id' => $invoice->id,
            'amount' => 500,
            'payment_method' => 'cash',
        ]);

        $invoice->refresh();

        $this->assertEquals(500, $invoice->amount_paid);
        $this->assertEquals(500, $invoice->balance);
        $this->assertEquals('partial', $invoice->status);
    }

    /**
     * Test payment number generation is unique
     */
    public function test_payment_number_is_unique(): void
    {
        $invoice = Invoice::factory()->create([
            'customer_id' => $this->customer->id,
            'vehicle_id' => $this->vehicle->id,
            'branch_id' => $this->branch->id,
            'total_amount' => 1000,
            'amount_paid' => 0,
            'balance' => 1000,
        ]);

        $payment1 = $this->paymentService->recordPayment([
            'invoice_id' => $invoice->id,
            'amount' => 200,
            'payment_method' => 'cash',
        ]);

        $payment2 = $this->paymentService->recordPayment([
            'invoice_id' => $invoice->id,
            'amount' => 300,
            'payment_method' => 'cash',
        ]);

        $this->assertNotEquals($payment1->payment_number, $payment2->payment_number);
    }

    /**
     * Test void payment reverses invoice balance
     */
    public function test_void_payment_reverses_balance(): void
    {
        $invoice = Invoice::factory()->create([
            'customer_id' => $this->customer->id,
            'vehicle_id' => $this->vehicle->id,
            'branch_id' => $this->branch->id,
            'total_amount' => 1000,
            'amount_paid' => 0,
            'balance' => 1000,
        ]);

        $payment = $this->paymentService->recordPayment([
            'invoice_id' => $invoice->id,
            'amount' => 500,
            'payment_method' => 'cash',
        ]);

        $this->paymentService->voidPayment($payment->id, 'Test void');

        $invoice->refresh();

        $this->assertEquals(0, $invoice->amount_paid);
        $this->assertEquals(1000, $invoice->balance);
    }
}
