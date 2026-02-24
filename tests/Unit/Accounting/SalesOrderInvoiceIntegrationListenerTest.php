<?php

namespace Tests\Unit\Accounting;

use Illuminate\Support\Facades\DB;
use Mockery;
use Modules\Accounting\Application\Listeners\HandleSalesOrderConfirmedListener;
use Modules\Accounting\Application\UseCases\CreateInvoiceUseCase;
use Modules\Sales\Domain\Events\OrderConfirmed;
use PHPUnit\Framework\TestCase;


class SalesOrderInvoiceIntegrationListenerTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function makeEvent(
        string  $orderId    = 'so-uuid-1',
        string  $tenantId   = 'tenant-1',
        ?string $customerId = 'cust-1',
        array   $lines      = [],
    ): OrderConfirmed {
        return new OrderConfirmed(
            orderId:    $orderId,
            tenantId:   $tenantId,
            customerId: $customerId,
            lines:      $lines,
        );
    }

    // -------------------------------------------------------------------------
    // Guard: skip when tenantId is empty
    // -------------------------------------------------------------------------

    public function test_skips_when_tenant_id_empty(): void
    {
        $useCase = Mockery::mock(CreateInvoiceUseCase::class);
        $useCase->shouldNotReceive('execute');

        $event = $this->makeEvent(tenantId: '');

        (new HandleSalesOrderConfirmedListener($useCase))->handle($event);

        $this->addToAssertionCount(1);
    }

    // -------------------------------------------------------------------------
    // Guard: skip when no valid lines
    // -------------------------------------------------------------------------

    public function test_skips_when_lines_empty(): void
    {
        $useCase = Mockery::mock(CreateInvoiceUseCase::class);
        $useCase->shouldNotReceive('execute');

        $event = $this->makeEvent(lines: []);

        (new HandleSalesOrderConfirmedListener($useCase))->handle($event);

        $this->addToAssertionCount(1);
    }

    public function test_skips_line_without_product_id(): void
    {
        $useCase = Mockery::mock(CreateInvoiceUseCase::class);
        $useCase->shouldNotReceive('execute');

        $event = $this->makeEvent(lines: [
            ['product_id' => null, 'description' => 'Item', 'qty' => '1', 'uom' => 'pcs', 'unit_price' => '10.00', 'tax_rate' => '0'],
        ]);

        (new HandleSalesOrderConfirmedListener($useCase))->handle($event);

        $this->addToAssertionCount(1);
    }

    public function test_skips_line_with_zero_qty(): void
    {
        $useCase = Mockery::mock(CreateInvoiceUseCase::class);
        $useCase->shouldNotReceive('execute');

        $event = $this->makeEvent(lines: [
            ['product_id' => 'prod-1', 'description' => 'Item', 'qty' => '0', 'uom' => 'pcs', 'unit_price' => '10.00', 'tax_rate' => '0'],
        ]);

        (new HandleSalesOrderConfirmedListener($useCase))->handle($event);

        $this->addToAssertionCount(1);
    }

    public function test_skips_line_without_unit_price(): void
    {
        $useCase = Mockery::mock(CreateInvoiceUseCase::class);
        $useCase->shouldNotReceive('execute');

        $event = $this->makeEvent(lines: [
            ['product_id' => 'prod-1', 'description' => 'Item', 'qty' => '2', 'uom' => 'pcs'],
        ]);

        (new HandleSalesOrderConfirmedListener($useCase))->handle($event);

        $this->addToAssertionCount(1);
    }

    public function test_skips_when_all_lines_invalid(): void
    {
        $useCase = Mockery::mock(CreateInvoiceUseCase::class);
        $useCase->shouldNotReceive('execute');

        $event = $this->makeEvent(lines: [
            ['product_id' => null,     'description' => 'No product', 'qty' => '1',  'unit_price' => '5.00'],
            ['product_id' => 'prod-2', 'description' => 'Zero qty',   'qty' => '0',  'unit_price' => '5.00'],
            ['product_id' => 'prod-3', 'description' => 'No price',   'qty' => '1'],
        ]);

        (new HandleSalesOrderConfirmedListener($useCase))->handle($event);

        $this->addToAssertionCount(1);
    }

    // -------------------------------------------------------------------------
    // Happy path: creates invoice
    // -------------------------------------------------------------------------

    public function test_creates_invoice_for_valid_line(): void
    {
        $useCase = Mockery::mock(CreateInvoiceUseCase::class);
        $useCase->shouldReceive('execute')
            ->once()
            ->withArgs(function (array $data) {
                return $data['tenant_id']    === 'tenant-1'
                    && $data['invoice_type'] === 'customer_invoice'
                    && $data['partner_id']   === 'cust-1'
                    && $data['partner_type'] === 'customer'
                    && count($data['lines']) === 1
                    && $data['lines'][0]['product_id']  === 'prod-1'
                    && $data['lines'][0]['quantity']    === '2.00000000'
                    && $data['lines'][0]['unit_price']  === '50.00';
            })
            ->andReturn((object) ['id' => 'inv-uuid-1']);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());

        $event = $this->makeEvent(lines: [
            ['product_id' => 'prod-1', 'description' => 'Widget', 'qty' => '2.00000000', 'uom' => 'pcs', 'unit_price' => '50.00', 'tax_rate' => '0.1'],
        ]);

        (new HandleSalesOrderConfirmedListener($useCase))->handle($event);

        $this->addToAssertionCount(1);
    }

    public function test_creates_invoice_with_multiple_lines(): void
    {
        $useCase = Mockery::mock(CreateInvoiceUseCase::class);
        $useCase->shouldReceive('execute')
            ->once()
            ->withArgs(function (array $data) {
                return count($data['lines']) === 2
                    && $data['lines'][0]['product_id'] === 'prod-a'
                    && $data['lines'][1]['product_id'] === 'prod-b';
            })
            ->andReturn((object) ['id' => 'inv-uuid-2']);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());

        $event = $this->makeEvent(lines: [
            ['product_id' => 'prod-a', 'description' => 'Item A', 'qty' => '3', 'uom' => 'pcs', 'unit_price' => '100.00', 'tax_rate' => '0'],
            ['product_id' => 'prod-b', 'description' => 'Item B', 'qty' => '5', 'uom' => 'box', 'unit_price' => '25.00',  'tax_rate' => '0.1'],
        ]);

        (new HandleSalesOrderConfirmedListener($useCase))->handle($event);

        $this->addToAssertionCount(1);
    }

    public function test_filters_invalid_lines_and_creates_with_valid_only(): void
    {
        $useCase = Mockery::mock(CreateInvoiceUseCase::class);
        $useCase->shouldReceive('execute')
            ->once()
            ->withArgs(function (array $data) {
                return count($data['lines']) === 1
                    && $data['lines'][0]['product_id'] === 'prod-valid';
            })
            ->andReturn((object) ['id' => 'inv-uuid-3']);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());

        $event = $this->makeEvent(lines: [
            ['product_id' => null,          'description' => 'No product', 'qty' => '1', 'unit_price' => '10.00'],
            ['product_id' => 'prod-valid',  'description' => 'Valid',       'qty' => '2', 'unit_price' => '30.00', 'tax_rate' => '0'],
            ['product_id' => 'prod-noprice','description' => 'No price',   'qty' => '1'],
        ]);

        (new HandleSalesOrderConfirmedListener($useCase))->handle($event);

        $this->addToAssertionCount(1);
    }

    public function test_includes_notes_referencing_sales_order_id(): void
    {
        $useCase = Mockery::mock(CreateInvoiceUseCase::class);
        $useCase->shouldReceive('execute')
            ->once()
            ->withArgs(function (array $data) {
                return str_contains($data['notes'] ?? '', 'so-uuid-99');
            })
            ->andReturn((object) ['id' => 'inv-uuid-4']);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());

        $event = $this->makeEvent(orderId: 'so-uuid-99', lines: [
            ['product_id' => 'prod-1', 'description' => 'Item', 'qty' => '1', 'unit_price' => '20.00', 'tax_rate' => '0'],
        ]);

        (new HandleSalesOrderConfirmedListener($useCase))->handle($event);

        $this->addToAssertionCount(1);
    }

    public function test_passes_customer_id_as_partner_id(): void
    {
        $useCase = Mockery::mock(CreateInvoiceUseCase::class);
        $useCase->shouldReceive('execute')
            ->once()
            ->withArgs(function (array $data) {
                return $data['partner_id'] === 'customer-uuid-42';
            })
            ->andReturn((object) ['id' => 'inv-uuid-5']);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());

        $event = $this->makeEvent(customerId: 'customer-uuid-42', lines: [
            ['product_id' => 'prod-1', 'description' => 'Item', 'qty' => '1', 'unit_price' => '15.00'],
        ]);

        (new HandleSalesOrderConfirmedListener($useCase))->handle($event);

        $this->addToAssertionCount(1);
    }

    public function test_defaults_tax_rate_to_zero_when_missing(): void
    {
        $useCase = Mockery::mock(CreateInvoiceUseCase::class);
        $useCase->shouldReceive('execute')
            ->once()
            ->withArgs(function (array $data) {
                return $data['lines'][0]['tax_rate'] === '0';
            })
            ->andReturn((object) ['id' => 'inv-uuid-6']);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());

        $event = $this->makeEvent(lines: [
            ['product_id' => 'prod-1', 'description' => 'Item', 'qty' => '1', 'unit_price' => '20.00'],
        ]);

        (new HandleSalesOrderConfirmedListener($useCase))->handle($event);

        $this->addToAssertionCount(1);
    }

    // -------------------------------------------------------------------------
    // Graceful degradation: invoice creation failure does not propagate
    // -------------------------------------------------------------------------

    public function test_graceful_degradation_when_create_invoice_throws(): void
    {
        $useCase = Mockery::mock(CreateInvoiceUseCase::class);
        $useCase->shouldReceive('execute')
            ->once()
            ->andThrow(new \RuntimeException('Accounting service unavailable'));

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());

        $event = $this->makeEvent(lines: [
            ['product_id' => 'prod-1', 'description' => 'Item', 'qty' => '1', 'unit_price' => '10.00'],
        ]);

        // Must not throw — graceful degradation
        (new HandleSalesOrderConfirmedListener($useCase))->handle($event);

        $this->addToAssertionCount(1);
    }
}
