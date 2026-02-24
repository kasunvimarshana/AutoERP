<?php

namespace Tests\Unit\Accounting;

use Mockery;
use Modules\Accounting\Application\Listeners\HandleGoodsReceivedListener;
use Modules\Accounting\Application\UseCases\CreateInvoiceUseCase;
use Modules\Purchase\Domain\Events\GoodsReceived;
use PHPUnit\Framework\TestCase;


class GoodsReceivedVendorBillListenerTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function makeEvent(
        string  $poId     = 'po-uuid-1',
        string  $grnId    = 'grn-uuid-1',
        string  $tenantId = 'tenant-1',
        array   $lines    = [],
        ?string $vendorId = 'vendor-1',
    ): GoodsReceived {
        return new GoodsReceived(
            poId:     $poId,
            grnId:    $grnId,
            tenantId: $tenantId,
            lines:    $lines,
            vendorId: $vendorId,
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

        (new HandleGoodsReceivedListener($useCase))->handle($event);

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

        (new HandleGoodsReceivedListener($useCase))->handle($event);

        $this->addToAssertionCount(1);
    }

    public function test_skips_line_without_product_id(): void
    {
        $useCase = Mockery::mock(CreateInvoiceUseCase::class);
        $useCase->shouldNotReceive('execute');

        $event = $this->makeEvent(lines: [
            ['product_id' => null, 'qty_accepted' => '1', 'location_id' => null, 'unit_price' => '10.00', 'tax_rate' => '0', 'description' => 'Item'],
        ]);

        (new HandleGoodsReceivedListener($useCase))->handle($event);

        $this->addToAssertionCount(1);
    }

    public function test_skips_line_with_zero_qty(): void
    {
        $useCase = Mockery::mock(CreateInvoiceUseCase::class);
        $useCase->shouldNotReceive('execute');

        $event = $this->makeEvent(lines: [
            ['product_id' => 'prod-1', 'qty_accepted' => '0', 'location_id' => null, 'unit_price' => '10.00', 'tax_rate' => '0', 'description' => 'Item'],
        ]);

        (new HandleGoodsReceivedListener($useCase))->handle($event);

        $this->addToAssertionCount(1);
    }

    public function test_skips_line_without_unit_price(): void
    {
        $useCase = Mockery::mock(CreateInvoiceUseCase::class);
        $useCase->shouldNotReceive('execute');

        $event = $this->makeEvent(lines: [
            ['product_id' => 'prod-1', 'qty_accepted' => '2', 'location_id' => null, 'description' => 'Item'],
        ]);

        (new HandleGoodsReceivedListener($useCase))->handle($event);

        $this->addToAssertionCount(1);
    }

    public function test_skips_line_with_null_unit_price(): void
    {
        $useCase = Mockery::mock(CreateInvoiceUseCase::class);
        $useCase->shouldNotReceive('execute');

        $event = $this->makeEvent(lines: [
            ['product_id' => 'prod-1', 'qty_accepted' => '2', 'location_id' => null, 'unit_price' => null, 'tax_rate' => '0', 'description' => 'Item'],
        ]);

        (new HandleGoodsReceivedListener($useCase))->handle($event);

        $this->addToAssertionCount(1);
    }

    public function test_skips_when_all_lines_invalid(): void
    {
        $useCase = Mockery::mock(CreateInvoiceUseCase::class);
        $useCase->shouldNotReceive('execute');

        $event = $this->makeEvent(lines: [
            ['product_id' => null,     'qty_accepted' => '1', 'unit_price' => '5.00',  'description' => 'No product'],
            ['product_id' => 'prod-2', 'qty_accepted' => '0', 'unit_price' => '5.00',  'description' => 'Zero qty'],
            ['product_id' => 'prod-3', 'qty_accepted' => '1', 'unit_price' => null,    'description' => 'No price'],
        ]);

        (new HandleGoodsReceivedListener($useCase))->handle($event);

        $this->addToAssertionCount(1);
    }

    // -------------------------------------------------------------------------
    // Happy path: creates vendor bill
    // -------------------------------------------------------------------------

    public function test_creates_vendor_bill_for_valid_line(): void
    {
        $useCase = Mockery::mock(CreateInvoiceUseCase::class);
        $useCase->shouldReceive('execute')
            ->once()
            ->withArgs(function (array $data) {
                return $data['tenant_id']    === 'tenant-1'
                    && $data['invoice_type'] === 'vendor_bill'
                    && $data['partner_id']   === 'vendor-1'
                    && $data['partner_type'] === 'vendor'
                    && count($data['lines']) === 1
                    && $data['lines'][0]['product_id']  === 'prod-1'
                    && $data['lines'][0]['quantity']    === '3.00000000'
                    && $data['lines'][0]['unit_price']  === '25.00';
            })
            ->andReturn((object) ['id' => 'bill-uuid-1']);

        $event = $this->makeEvent(lines: [
            ['product_id' => 'prod-1', 'qty_accepted' => '3.00000000', 'location_id' => 'loc-1', 'unit_price' => '25.00', 'tax_rate' => '0.1', 'description' => 'Widgets'],
        ]);

        (new HandleGoodsReceivedListener($useCase))->handle($event);

        $this->addToAssertionCount(1);
    }

    public function test_creates_vendor_bill_with_multiple_lines(): void
    {
        $useCase = Mockery::mock(CreateInvoiceUseCase::class);
        $useCase->shouldReceive('execute')
            ->once()
            ->withArgs(function (array $data) {
                return count($data['lines']) === 2
                    && $data['lines'][0]['product_id'] === 'prod-a'
                    && $data['lines'][1]['product_id'] === 'prod-b';
            })
            ->andReturn((object) ['id' => 'bill-uuid-2']);

        $event = $this->makeEvent(lines: [
            ['product_id' => 'prod-a', 'qty_accepted' => '10', 'location_id' => null, 'unit_price' => '5.00',  'tax_rate' => '0',   'description' => 'Item A'],
            ['product_id' => 'prod-b', 'qty_accepted' => '2',  'location_id' => null, 'unit_price' => '150.00','tax_rate' => '0.1', 'description' => 'Item B'],
        ]);

        (new HandleGoodsReceivedListener($useCase))->handle($event);

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
            ->andReturn((object) ['id' => 'bill-uuid-3']);

        $event = $this->makeEvent(lines: [
            ['product_id' => null,          'qty_accepted' => '1', 'unit_price' => '10.00', 'description' => 'No product'],
            ['product_id' => 'prod-valid',  'qty_accepted' => '2', 'unit_price' => '30.00', 'tax_rate' => '0', 'description' => 'Valid'],
            ['product_id' => 'prod-noprice','qty_accepted' => '1', 'unit_price' => null,    'description' => 'No price'],
        ]);

        (new HandleGoodsReceivedListener($useCase))->handle($event);

        $this->addToAssertionCount(1);
    }

    public function test_includes_notes_referencing_grn_and_po_ids(): void
    {
        $useCase = Mockery::mock(CreateInvoiceUseCase::class);
        $useCase->shouldReceive('execute')
            ->once()
            ->withArgs(function (array $data) {
                return str_contains($data['notes'] ?? '', 'grn-uuid-99')
                    && str_contains($data['notes'] ?? '', 'po-uuid-42');
            })
            ->andReturn((object) ['id' => 'bill-uuid-4']);

        $event = $this->makeEvent(poId: 'po-uuid-42', grnId: 'grn-uuid-99', lines: [
            ['product_id' => 'prod-1', 'qty_accepted' => '1', 'unit_price' => '20.00', 'tax_rate' => '0', 'description' => 'Item'],
        ]);

        (new HandleGoodsReceivedListener($useCase))->handle($event);

        $this->addToAssertionCount(1);
    }

    public function test_passes_vendor_id_as_partner_id(): void
    {
        $useCase = Mockery::mock(CreateInvoiceUseCase::class);
        $useCase->shouldReceive('execute')
            ->once()
            ->withArgs(function (array $data) {
                return $data['partner_id'] === 'vendor-uuid-77';
            })
            ->andReturn((object) ['id' => 'bill-uuid-5']);

        $event = $this->makeEvent(vendorId: 'vendor-uuid-77', lines: [
            ['product_id' => 'prod-1', 'qty_accepted' => '1', 'unit_price' => '15.00', 'description' => 'Item'],
        ]);

        (new HandleGoodsReceivedListener($useCase))->handle($event);

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
            ->andReturn((object) ['id' => 'bill-uuid-6']);

        $event = $this->makeEvent(lines: [
            ['product_id' => 'prod-1', 'qty_accepted' => '1', 'unit_price' => '20.00', 'description' => 'Item'],
        ]);

        (new HandleGoodsReceivedListener($useCase))->handle($event);

        $this->addToAssertionCount(1);
    }

    public function test_invoice_type_is_vendor_bill(): void
    {
        $useCase = Mockery::mock(CreateInvoiceUseCase::class);
        $useCase->shouldReceive('execute')
            ->once()
            ->withArgs(function (array $data) {
                return $data['invoice_type'] === 'vendor_bill'
                    && $data['partner_type'] === 'vendor';
            })
            ->andReturn((object) ['id' => 'bill-uuid-7']);

        $event = $this->makeEvent(lines: [
            ['product_id' => 'prod-1', 'qty_accepted' => '2', 'unit_price' => '50.00', 'tax_rate' => '0', 'description' => 'Item'],
        ]);

        (new HandleGoodsReceivedListener($useCase))->handle($event);

        $this->addToAssertionCount(1);
    }

    // -------------------------------------------------------------------------
    // Graceful degradation: vendor bill creation failure does not propagate
    // -------------------------------------------------------------------------

    public function test_graceful_degradation_when_create_invoice_throws(): void
    {
        $useCase = Mockery::mock(CreateInvoiceUseCase::class);
        $useCase->shouldReceive('execute')
            ->once()
            ->andThrow(new \RuntimeException('Accounting service unavailable'));

        $event = $this->makeEvent(lines: [
            ['product_id' => 'prod-1', 'qty_accepted' => '1', 'unit_price' => '10.00', 'description' => 'Item'],
        ]);

        // Must not throw — graceful degradation
        (new HandleGoodsReceivedListener($useCase))->handle($event);

        $this->addToAssertionCount(1);
    }
}
