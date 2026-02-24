<?php

namespace Tests\Unit\Purchase;

use Illuminate\Container\Container;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Mockery;
use Modules\Purchase\Application\UseCases\ApprovePurchaseOrderUseCase;
use Modules\Purchase\Application\UseCases\CreatePurchaseOrderUseCase;
use Modules\Purchase\Application\UseCases\ReceiveGoodsUseCase;
use Modules\Purchase\Domain\Contracts\GoodsReceiptRepositoryInterface;
use Modules\Purchase\Domain\Contracts\PurchaseOrderRepositoryInterface;
use Modules\Purchase\Domain\Events\GoodsReceived;
use Modules\Purchase\Domain\Events\PurchaseOrderApproved;
use Modules\Purchase\Domain\Events\PurchaseOrderCreated;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Purchase module use cases.
 *
 * Covers purchase order creation with BCMath line totals, approval lifecycle
 * guards, and goods receipt creation.
 *
 * ApprovePurchaseOrderUseCase calls auth()->id() for the approved_by field.
 * We register a null-returning auth mock in the container so that
 * app(AuthFactory::class) resolves without a full Laravel bootstrap.
 */
class PurchaseUseCaseTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $authMock = Mockery::mock(AuthFactory::class);
        $authMock->shouldReceive('user')->andReturn(null)->byDefault();
        $authMock->shouldReceive('id')->andReturn(null)->byDefault();
        $authMock->shouldReceive('guard')->andReturn($authMock)->byDefault();
        Container::getInstance()->instance(AuthFactory::class, $authMock);
        Container::getInstance()->instance('auth', $authMock);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function makePO(string $status = 'draft'): object
    {
        return (object) [
            'id'        => 'po-uuid-1',
            'tenant_id' => 'tenant-uuid-1',
            'number'    => 'PO-2026-000001',
            'status'    => $status,
            'subtotal'  => '200.00000000',
            'tax_total' => '20.00000000',
            'total'     => '220.00000000',
        ];
    }

    private function makeGRN(): object
    {
        return (object) [
            'id'                => 'grn-uuid-1',
            'purchase_order_id' => 'po-uuid-1',
            'tenant_id'         => 'tenant-uuid-1',
        ];
    }

    // -------------------------------------------------------------------------
    // CreatePurchaseOrderUseCase
    // -------------------------------------------------------------------------

    public function test_create_po_sets_status_draft_and_dispatches_event(): void
    {
        $po     = $this->makePO();
        $poRepo = Mockery::mock(PurchaseOrderRepositoryInterface::class);

        $poRepo->shouldReceive('nextNumber')->andReturn('PO-2026-000001');
        $poRepo->shouldReceive('create')
            ->once()
            ->withArgs(fn ($data) => $data['status'] === 'draft')
            ->andReturn($po);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')
            ->once()
            ->withArgs(fn ($event) => $event instanceof PurchaseOrderCreated
                && $event->purchaseOrderId === 'po-uuid-1');

        $useCase = new CreatePurchaseOrderUseCase($poRepo);
        $result  = $useCase->execute([
            'tenant_id'  => 'tenant-uuid-1',
            'vendor_id'  => 'vendor-uuid-1',
        ]);

        $this->assertSame('draft', $result->status);
    }

    public function test_create_po_calculates_line_totals_with_bcmath(): void
    {
        $po = (object) [
            'id'        => 'po-uuid-2',
            'status'    => 'draft',
            'subtotal'  => '200.00000000',
            'tax_total' => '20.00000000',
            'total'     => '220.00000000',
        ];
        $poRepo = Mockery::mock(PurchaseOrderRepositoryInterface::class);

        $poRepo->shouldReceive('nextNumber')->andReturn('PO-2026-000002');
        $poRepo->shouldReceive('create')
            ->once()
            ->withArgs(fn ($data) => $data['subtotal'] === '200.00000000'
                && $data['tax_total'] === '20.00000000'
                && $data['total'] === '220.00000000')
            ->andReturn($po);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')->once();

        $useCase = new CreatePurchaseOrderUseCase($poRepo);
        $result  = $useCase->execute([
            'tenant_id' => 'tenant-uuid-1',
            'vendor_id' => 'vendor-uuid-1',
            'lines'     => [
                ['qty' => '2', 'unit_price' => '100', 'tax_rate' => '10'],
            ],
        ]);

        $this->assertSame('220.00000000', $result->total);
    }

    // -------------------------------------------------------------------------
    // ApprovePurchaseOrderUseCase
    // -------------------------------------------------------------------------

    public function test_approve_po_throws_when_not_found(): void
    {
        $poRepo = Mockery::mock(PurchaseOrderRepositoryInterface::class);
        $poRepo->shouldReceive('findById')->andReturn(null);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());

        $useCase = new ApprovePurchaseOrderUseCase($poRepo);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/not found/i');

        $useCase->execute('missing-id');
    }

    public function test_approve_po_throws_when_not_draft(): void
    {
        $poRepo = Mockery::mock(PurchaseOrderRepositoryInterface::class);
        $poRepo->shouldReceive('findById')->andReturn($this->makePO('approved'));

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());

        $useCase = new ApprovePurchaseOrderUseCase($poRepo);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/only draft/i');

        $useCase->execute('po-uuid-1');
    }

    public function test_approve_po_transitions_and_dispatches_event(): void
    {
        $draft    = $this->makePO('draft');
        $approved = (object) array_merge((array) $draft, ['status' => 'approved']);

        $poRepo = Mockery::mock(PurchaseOrderRepositoryInterface::class);
        $poRepo->shouldReceive('findById')->andReturn($draft);
        $poRepo->shouldReceive('update')
            ->once()
            ->withArgs(fn ($id, $data) => $data['status'] === 'approved')
            ->andReturn($approved);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')
            ->once()
            ->withArgs(fn ($event) => $event instanceof PurchaseOrderApproved
                && $event->purchaseOrderId === 'po-uuid-1');

        $useCase = new ApprovePurchaseOrderUseCase($poRepo);
        $result  = $useCase->execute('po-uuid-1');

        $this->assertSame('approved', $result->status);
    }

    // -------------------------------------------------------------------------
    // ReceiveGoodsUseCase
    // -------------------------------------------------------------------------

    public function test_receive_goods_throws_when_po_not_found(): void
    {
        $poRepo  = Mockery::mock(PurchaseOrderRepositoryInterface::class);
        $grnRepo = Mockery::mock(GoodsReceiptRepositoryInterface::class);

        $poRepo->shouldReceive('findById')->andReturn(null);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());

        $useCase = new ReceiveGoodsUseCase($poRepo, $grnRepo);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/not found/i');

        $useCase->execute('missing-id', []);
    }

    public function test_receive_goods_throws_when_po_not_approved(): void
    {
        $poRepo  = Mockery::mock(PurchaseOrderRepositoryInterface::class);
        $grnRepo = Mockery::mock(GoodsReceiptRepositoryInterface::class);

        $poRepo->shouldReceive('findById')->andReturn($this->makePO('draft'));

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());

        $useCase = new ReceiveGoodsUseCase($poRepo, $grnRepo);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/cannot receive/i');

        $useCase->execute('po-uuid-1', []);
    }

    public function test_receive_goods_creates_grn_and_dispatches_event(): void
    {
        $po  = $this->makePO('approved');
        $grn = $this->makeGRN();

        $poRepo  = Mockery::mock(PurchaseOrderRepositoryInterface::class);
        $grnRepo = Mockery::mock(GoodsReceiptRepositoryInterface::class);

        $poRepo->shouldReceive('findById')->andReturn($po);
        $grnRepo->shouldReceive('create')
            ->once()
            ->withArgs(fn ($data) => $data['purchase_order_id'] === 'po-uuid-1')
            ->andReturn($grn);
        $poRepo->shouldReceive('update')
            ->once()
            ->withArgs(fn ($id, $data) => $data['status'] === 'partially_received');

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')
            ->once()
            ->withArgs(fn ($event) => $event instanceof GoodsReceived
                && $event->poId === 'po-uuid-1'
                && $event->grnId === 'grn-uuid-1');

        $useCase = new ReceiveGoodsUseCase($poRepo, $grnRepo);
        $result  = $useCase->execute('po-uuid-1', [
            'warehouse_id' => 'wh-uuid-1',
            'received_by'  => 'user-uuid-1',
        ]);

        $this->assertSame('grn-uuid-1', $result->id);
    }
}
