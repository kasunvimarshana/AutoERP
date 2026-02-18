<?php

declare(strict_types=1);

namespace Modules\Sales\Tests\Unit\Listeners;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Mockery;
use Modules\Accounting\Repositories\Contracts\InvoiceLineRepositoryInterface;
use Modules\Accounting\Repositories\Contracts\InvoiceRepositoryInterface;
use Modules\Sales\Events\SalesOrderConfirmed;
use Modules\Sales\Listeners\CreateAccountingEntryListener;
use Tests\TestCase;

class CreateAccountingEntryListenerTest extends TestCase
{
    private $invoiceRepository;

    private $invoiceLineRepository;

    private $listener;

    protected function setUp(): void
    {
        parent::setUp();

        $this->invoiceRepository = Mockery::mock(InvoiceRepositoryInterface::class);
        $this->invoiceLineRepository = Mockery::mock(InvoiceLineRepositoryInterface::class);

        $this->listener = new CreateAccountingEntryListener(
            $this->invoiceRepository,
            $this->invoiceLineRepository
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_it_creates_invoice_when_sales_order_confirmed_event_fires(): void
    {
        // Arrange
        DB::shouldReceive('beginTransaction')->once();
        DB::shouldReceive('commit')->once();
        Log::shouldReceive('info')->once();

        $salesOrder = Mockery::mock('Modules\Sales\Entities\SalesOrder')->shouldIgnoreMissing();
        $salesOrder->id = 'order-uuid-123';
        $salesOrder->tenant_id = 'tenant-uuid-123';
        $salesOrder->customer_id = 'customer-uuid-123';
        $salesOrder->order_number = 'SO-20240209-001';
        $salesOrder->subtotal = 1000.00;
        $salesOrder->tax_amount = 80.00;
        $salesOrder->discount_amount = 50.00;
        $salesOrder->total_amount = 1030.00;
        $salesOrder->currency = 'USD';
        $salesOrder->shouldReceive('getAttribute')->with('lines')->andReturn(collect());

        $invoice = Mockery::mock('Illuminate\Database\Eloquent\Model')->shouldIgnoreMissing();
        $invoice->id = 'invoice-uuid-123';
        $invoice->invoice_number = 'INV-20240209-0001';
        $invoice->total_amount = 1030.00;

        $this->invoiceRepository
            ->shouldReceive('create')
            ->once()
            ->withArgs(function ($data) use ($salesOrder) {
                return $data['tenant_id'] === $salesOrder->tenant_id
                    && $data['customer_id'] === $salesOrder->customer_id
                    && $data['invoice_type'] === 'sales'
                    && $data['reference_type'] === 'sales_order'
                    && $data['reference_id'] === $salesOrder->id
                    && $data['reference_number'] === $salesOrder->order_number
                    && $data['total_amount'] === $salesOrder->total_amount
                    && $data['status'] === 'draft';
            })
            ->andReturn($invoice);

        $event = new SalesOrderConfirmed($salesOrder);

        // Act
        $this->listener->handle($event);

        // Assert
        $this->assertTrue(true); // If we got here without exceptions, test passed
    }

    public function test_it_creates_invoice_lines_for_each_order_line(): void
    {
        // Arrange
        DB::shouldReceive('beginTransaction')->once();
        DB::shouldReceive('commit')->once();
        Log::shouldReceive('info')->once();

        $orderLine1 = Mockery::mock();
        $orderLine1->product_id = 'product-uuid-1';
        $orderLine1->description = 'Product 1';
        $orderLine1->quantity = 5;
        $orderLine1->unit_price = 100.00;
        $orderLine1->discount_percentage = 10.00;
        $orderLine1->tax_rate = 8.00;
        $orderLine1->line_total = 450.00;

        $orderLine2 = Mockery::mock();
        $orderLine2->product_id = 'product-uuid-2';
        $orderLine2->description = 'Product 2';
        $orderLine2->quantity = 3;
        $orderLine2->unit_price = 200.00;
        $orderLine2->discount_percentage = 0.00;
        $orderLine2->tax_rate = 8.00;
        $orderLine2->line_total = 600.00;

        $salesOrder = Mockery::mock('Modules\Sales\Entities\SalesOrder')->shouldIgnoreMissing();
        $salesOrder->id = 'order-uuid-123';
        $salesOrder->tenant_id = 'tenant-uuid-123';
        $salesOrder->customer_id = 'customer-uuid-123';
        $salesOrder->order_number = 'SO-20240209-001';
        $salesOrder->subtotal = 1000.00;
        $salesOrder->tax_amount = 80.00;
        $salesOrder->discount_amount = 50.00;
        $salesOrder->total_amount = 1030.00;
        $salesOrder->currency = 'USD';
        $salesOrder->shouldReceive('getAttribute')->with('lines')->andReturn(collect([
            $orderLine1,
            $orderLine2,
        ]));

        $invoice = Mockery::mock('Illuminate\Database\Eloquent\Model')->shouldIgnoreMissing();
        $invoice->id = 'invoice-uuid-123';
        $invoice->invoice_number = 'INV-20240209-0001';
        $invoice->total_amount = 1030.00;

        $this->invoiceRepository
            ->shouldReceive('create')
            ->once()
            ->andReturn($invoice);

        // Expect two invoice lines to be created
        $this->invoiceLineRepository
            ->shouldReceive('create')
            ->twice()
            ->withArgs(function ($data) use ($salesOrder, $invoice) {
                return $data['tenant_id'] === $salesOrder->tenant_id
                    && $data['invoice_id'] === $invoice->id
                    && isset($data['product_id'])
                    && isset($data['quantity'])
                    && isset($data['unit_price']);
            })
            ->andReturn(Mockery::mock());

        $event = new SalesOrderConfirmed($salesOrder);

        // Act
        $this->listener->handle($event);

        // Assert
        $this->assertTrue(true);
    }

    public function test_it_rolls_back_transaction_on_failure(): void
    {
        // Arrange
        DB::shouldReceive('beginTransaction')->once();
        DB::shouldReceive('rollBack')->once();
        DB::shouldReceive('commit')->never();
        Log::shouldReceive('error')->once();

        $salesOrder = Mockery::mock('Modules\Sales\Entities\SalesOrder')->shouldIgnoreMissing();
        $salesOrder->id = 'order-uuid-123';
        $salesOrder->tenant_id = 'tenant-uuid-123';
        $salesOrder->customer_id = 'customer-uuid-123';
        $salesOrder->order_number = 'SO-20240209-001';
        $salesOrder->subtotal = 1000.00;
        $salesOrder->tax_amount = 80.00;
        $salesOrder->discount_amount = 50.00;
        $salesOrder->total_amount = 1030.00;
        $salesOrder->currency = 'USD';

        // Simulate repository failure
        $this->invoiceRepository
            ->shouldReceive('create')
            ->once()
            ->andThrow(new \Exception('Database error'));

        $event = new SalesOrderConfirmed($salesOrder);

        // Act & Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Database error');

        $this->listener->handle($event);
    }

    public function test_it_logs_invoice_creation_success(): void
    {
        // Arrange
        DB::shouldReceive('beginTransaction')->once();
        DB::shouldReceive('commit')->once();

        $salesOrder = Mockery::mock('Modules\Sales\Entities\SalesOrder')->shouldIgnoreMissing();
        $salesOrder->id = 'order-uuid-123';
        $salesOrder->tenant_id = 'tenant-uuid-123';
        $salesOrder->customer_id = 'customer-uuid-123';
        $salesOrder->order_number = 'SO-20240209-001';
        $salesOrder->subtotal = 1000.00;
        $salesOrder->tax_amount = 80.00;
        $salesOrder->discount_amount = 50.00;
        $salesOrder->total_amount = 1030.00;
        $salesOrder->currency = 'USD';
        $salesOrder->shouldReceive('getAttribute')->with('lines')->andReturn(collect());

        $invoice = Mockery::mock('Illuminate\Database\Eloquent\Model')->shouldIgnoreMissing();
        $invoice->id = 'invoice-uuid-123';
        $invoice->invoice_number = 'INV-20240209-0001';
        $invoice->total_amount = 1030.00;

        $this->invoiceRepository
            ->shouldReceive('create')
            ->once()
            ->andReturn($invoice);

        Log::shouldReceive('info')
            ->once()
            ->withArgs(function ($message, $context) use ($salesOrder, $invoice) {
                return $message === 'Accounting invoice created for sales order'
                    && $context['order_id'] === $salesOrder->id
                    && $context['order_number'] === $salesOrder->order_number
                    && $context['invoice_id'] === $invoice->id
                    && $context['invoice_number'] === $invoice->invoice_number
                    && $context['total_amount'] === $invoice->total_amount;
            });

        $event = new SalesOrderConfirmed($salesOrder);

        // Act
        $this->listener->handle($event);

        // Assert
        $this->assertTrue(true);
    }

    public function test_failed_method_logs_permanent_failure(): void
    {
        // Arrange
        $salesOrder = Mockery::mock('Modules\Sales\Entities\SalesOrder')->shouldIgnoreMissing();
        $salesOrder->id = 'order-uuid-123';

        $exception = new \Exception('Permanent failure');
        $event = new SalesOrderConfirmed($salesOrder);

        Log::shouldReceive('error')
            ->once()
            ->withArgs(function ($message, $context) use ($salesOrder, $exception) {
                return $message === 'Accounting invoice creation failed permanently'
                    && $context['order_id'] === $salesOrder->id
                    && $context['error'] === $exception->getMessage();
            });

        // Act
        $this->listener->failed($event, $exception);

        // Assert
        $this->assertTrue(true);
    }

    public function test_it_generates_unique_invoice_number(): void
    {
        // Arrange
        DB::shouldReceive('beginTransaction')->once();
        DB::shouldReceive('commit')->once();
        Log::shouldReceive('info')->once();

        $salesOrder = Mockery::mock('Modules\Sales\Entities\SalesOrder')->shouldIgnoreMissing();
        $salesOrder->id = 'order-uuid-123';
        $salesOrder->tenant_id = 'tenant-uuid-123';
        $salesOrder->customer_id = 'customer-uuid-123';
        $salesOrder->order_number = 'SO-20240209-001';
        $salesOrder->subtotal = 1000.00;
        $salesOrder->tax_amount = 80.00;
        $salesOrder->discount_amount = 50.00;
        $salesOrder->total_amount = 1030.00;
        $salesOrder->currency = 'USD';
        $salesOrder->shouldReceive('getAttribute')->with('lines')->andReturn(collect());

        $invoice = Mockery::mock('Illuminate\Database\Eloquent\Model')->shouldIgnoreMissing();
        $invoice->id = 'invoice-uuid-123';
        $invoice->invoice_number = 'INV-20240209-0001';
        $invoice->total_amount = 1030.00;

        $this->invoiceRepository
            ->shouldReceive('create')
            ->once()
            ->withArgs(function ($data) {
                // Verify invoice number format: INV-YYYYMMDD-XXXX
                return preg_match('/^INV-\d{8}-\d{4}$/', $data['invoice_number']) === 1;
            })
            ->andReturn($invoice);

        $event = new SalesOrderConfirmed($salesOrder);

        // Act
        $this->listener->handle($event);

        // Assert
        $this->assertTrue(true);
    }

    public function test_it_sets_correct_payment_terms(): void
    {
        // Arrange
        DB::shouldReceive('beginTransaction')->once();
        DB::shouldReceive('commit')->once();
        Log::shouldReceive('info')->once();

        $salesOrder = Mockery::mock('Modules\Sales\Entities\SalesOrder')->shouldIgnoreMissing();
        $salesOrder->id = 'order-uuid-123';
        $salesOrder->tenant_id = 'tenant-uuid-123';
        $salesOrder->customer_id = 'customer-uuid-123';
        $salesOrder->order_number = 'SO-20240209-001';
        $salesOrder->subtotal = 1000.00;
        $salesOrder->tax_amount = 80.00;
        $salesOrder->discount_amount = 50.00;
        $salesOrder->total_amount = 1030.00;
        $salesOrder->currency = 'USD';
        $salesOrder->shouldReceive('getAttribute')->with('lines')->andReturn(collect());

        $invoice = Mockery::mock('Illuminate\Database\Eloquent\Model')->shouldIgnoreMissing();
        $invoice->id = 'invoice-uuid-123';
        $invoice->invoice_number = 'INV-20240209-0001';
        $invoice->total_amount = 1030.00;

        $this->invoiceRepository
            ->shouldReceive('create')
            ->once()
            ->withArgs(function ($data) {
                // Verify due_date is 30 days after invoice_date
                $invoiceDate = \Carbon\Carbon::parse($data['invoice_date']);
                $dueDate = \Carbon\Carbon::parse($data['due_date']);
                $daysDiff = $invoiceDate->diffInDays($dueDate);

                return $daysDiff === 30
                    && $data['amount_paid'] === 0
                    && $data['amount_due'] === $data['total_amount'];
            })
            ->andReturn($invoice);

        $event = new SalesOrderConfirmed($salesOrder);

        // Act
        $this->listener->handle($event);

        // Assert
        $this->assertTrue(true);
    }
}
