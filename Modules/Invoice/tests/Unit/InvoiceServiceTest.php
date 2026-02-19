<?php

declare(strict_types=1);

namespace Modules\Invoice\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Customer\Models\Customer;
use Modules\Customer\Models\Vehicle;
use Modules\Invoice\Models\Invoice;
use Modules\Invoice\Models\InvoiceItem;
use Modules\Invoice\Repositories\InvoiceRepository;
use Modules\Invoice\Services\InvoiceService;
use Modules\Organization\Models\Branch;
use Tests\TestCase;

/**
 * Invoice Service Unit Test
 *
 * Tests InvoiceService business logic
 */
class InvoiceServiceTest extends TestCase
{
    use RefreshDatabase;

    private InvoiceService $invoiceService;

    private Customer $customer;

    private Vehicle $vehicle;

    private Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();

        $this->invoiceService = app(InvoiceService::class);
        $this->customer = Customer::factory()->create();
        $this->vehicle = Vehicle::factory()->create(['customer_id' => $this->customer->id]);
        $this->branch = Branch::factory()->create();
    }

    /**
     * Test invoice totals calculation
     */

    public function test_invoice_totals_calculation(): void
    {
        $invoice = Invoice::factory()->create([
            'customer_id' => $this->customer->id,
            'vehicle_id' => $this->vehicle->id,
            'branch_id' => $this->branch->id,
            'tax_rate' => 10,
            'discount_amount' => 0,
            'subtotal' => 0,
            'tax_amount' => 0,
            'total_amount' => 0,
            'amount_paid' => 0,
            'balance' => 0,
        ]);

        InvoiceItem::factory()->create([
            'invoice_id' => $invoice->id,
            'quantity' => 2,
            'unit_price' => 50,
            'total_price' => 100,
        ]);

        InvoiceItem::factory()->create([
            'invoice_id' => $invoice->id,
            'quantity' => 1,
            'unit_price' => 100,
            'total_price' => 100,
        ]);

        $recalculated = $this->invoiceService->recalculateTotals($invoice->id);

        $this->assertEquals(200, $recalculated->subtotal);
        $this->assertEquals(20, $recalculated->tax_amount);
        $this->assertEquals(220, $recalculated->total_amount);
        $this->assertEquals(220, $recalculated->balance);
    }

    /**
     * Test invoice number generation is unique
     */

    public function test_invoice_number_is_unique(): void
    {
        $invoice1 = $this->invoiceService->create([
            'customer_id' => $this->customer->id,
            'branch_id' => $this->branch->id,
        ]);

        $invoice2 = $this->invoiceService->create([
            'customer_id' => $this->customer->id,
            'branch_id' => $this->branch->id,
        ]);

        $this->assertNotEquals($invoice1->invoice_number, $invoice2->invoice_number);
    }

    /**
     * Test outstanding invoices query
     */

    public function test_get_outstanding_invoices(): void
    {
        Invoice::factory()->create([
            'customer_id' => $this->customer->id,
            'vehicle_id' => $this->vehicle->id,
            'branch_id' => $this->branch->id,
            'balance' => 100,
            'status' => 'pending',
        ]);

        Invoice::factory()->create([
            'customer_id' => $this->customer->id,
            'vehicle_id' => $this->vehicle->id,
            'branch_id' => $this->branch->id,
            'balance' => 0,
            'status' => 'paid',
        ]);

        $outstanding = $this->invoiceService->getOutstanding();

        $this->assertCount(1, $outstanding);
    }
}
