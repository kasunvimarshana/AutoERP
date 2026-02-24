<?php

namespace Tests\Unit\Purchase;

use Illuminate\Container\Container;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Mockery;
use Modules\Purchase\Application\UseCases\ApprovePurchaseRequisitionUseCase;
use Modules\Purchase\Application\UseCases\ConvertRequisitionToPurchaseOrderUseCase;
use Modules\Purchase\Application\UseCases\CreatePurchaseRequisitionUseCase;
use Modules\Purchase\Application\UseCases\RejectPurchaseRequisitionUseCase;
use Modules\Purchase\Domain\Contracts\PurchaseOrderRepositoryInterface;
use Modules\Purchase\Domain\Contracts\PurchaseRequisitionRepositoryInterface;
use Modules\Purchase\Domain\Events\PurchaseOrderCreated;
use Modules\Purchase\Domain\Events\PurchaseRequisitionApproved;
use Modules\Purchase\Domain\Events\PurchaseRequisitionCreated;
use Modules\Purchase\Domain\Events\PurchaseRequisitionRejected;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Purchase Requisition use cases.
 *
 * Covers:
 * - CreatePurchaseRequisitionUseCase (BCMath line totals, draft status, event)
 * - ApprovePurchaseRequisitionUseCase (not-found guard, status guard, approve + event)
 * - RejectPurchaseRequisitionUseCase (not-found guard, status guard, reject + event)
 * - ConvertRequisitionToPurchaseOrderUseCase (not-found guard, non-approved guard, PO creation + events)
 */
class PurchaseRequisitionUseCaseTest extends TestCase
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

    private function makeRequisition(string $status = 'draft'): object
    {
        return (object) [
            'id'           => 'pr-uuid-1',
            'tenant_id'    => 'tenant-uuid-1',
            'number'       => 'PR-2026-000001',
            'requested_by' => 'user-uuid-1',
            'status'       => $status,
            'total_amount' => '300.00000000',
        ];
    }

    // -------------------------------------------------------------------------
    // CreatePurchaseRequisitionUseCase
    // -------------------------------------------------------------------------

    public function test_create_requisition_sets_status_draft_and_dispatches_event(): void
    {
        $pr     = $this->makeRequisition();
        $repo   = Mockery::mock(PurchaseRequisitionRepositoryInterface::class);

        $repo->shouldReceive('nextNumber')->andReturn('PR-2026-000001');
        $repo->shouldReceive('create')
            ->once()
            ->withArgs(fn ($data) => $data['status'] === 'draft')
            ->andReturn($pr);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')
            ->once()
            ->withArgs(fn ($event) => $event instanceof PurchaseRequisitionCreated
                && $event->requisitionId === 'pr-uuid-1');

        $useCase = new CreatePurchaseRequisitionUseCase($repo);
        $result  = $useCase->execute(['tenant_id' => 'tenant-uuid-1']);

        $this->assertSame('draft', $result->status);
    }

    public function test_create_requisition_calculates_line_totals_with_bcmath(): void
    {
        $pr   = (object) [
            'id'           => 'pr-uuid-2',
            'status'       => 'draft',
            'total_amount' => '300.00000000',
        ];
        $repo = Mockery::mock(PurchaseRequisitionRepositoryInterface::class);

        $repo->shouldReceive('nextNumber')->andReturn('PR-2026-000002');
        $repo->shouldReceive('create')
            ->once()
            ->withArgs(fn ($data) => $data['total_amount'] === '300.00000000')
            ->andReturn($pr);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')->once();

        $useCase = new CreatePurchaseRequisitionUseCase($repo);
        $result  = $useCase->execute([
            'tenant_id' => 'tenant-uuid-1',
            'lines'     => [
                ['qty' => '2', 'unit_price' => '100', 'product_id' => 'prod-1'],
                ['qty' => '1', 'unit_price' => '100', 'product_id' => 'prod-2'],
            ],
        ]);

        $this->assertSame('300.00000000', $result->total_amount);
    }

    // -------------------------------------------------------------------------
    // ApprovePurchaseRequisitionUseCase
    // -------------------------------------------------------------------------

    public function test_approve_requisition_throws_when_not_found(): void
    {
        $repo = Mockery::mock(PurchaseRequisitionRepositoryInterface::class);
        $repo->shouldReceive('findById')->andReturn(null);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());

        $useCase = new ApprovePurchaseRequisitionUseCase($repo);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/not found/i');

        $useCase->execute('missing-id');
    }

    public function test_approve_requisition_throws_when_status_invalid(): void
    {
        $repo = Mockery::mock(PurchaseRequisitionRepositoryInterface::class);
        $repo->shouldReceive('findById')->andReturn($this->makeRequisition('approved'));

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());

        $useCase = new ApprovePurchaseRequisitionUseCase($repo);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/only draft or pending_approval/i');

        $useCase->execute('pr-uuid-1');
    }

    public function test_approve_requisition_transitions_and_dispatches_event(): void
    {
        $draft    = $this->makeRequisition('draft');
        $approved = (object) array_merge((array) $draft, ['status' => 'approved', 'approved_by' => null, 'approved_at' => now()]);

        $repo = Mockery::mock(PurchaseRequisitionRepositoryInterface::class);
        $repo->shouldReceive('findById')->andReturn($draft);
        $repo->shouldReceive('update')
            ->once()
            ->withArgs(fn ($id, $data) => $data['status'] === 'approved')
            ->andReturn($approved);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')
            ->once()
            ->withArgs(fn ($event) => $event instanceof PurchaseRequisitionApproved
                && $event->requisitionId === 'pr-uuid-1');

        $useCase = new ApprovePurchaseRequisitionUseCase($repo);
        $result  = $useCase->execute('pr-uuid-1');

        $this->assertSame('approved', $result->status);
    }

    // -------------------------------------------------------------------------
    // RejectPurchaseRequisitionUseCase
    // -------------------------------------------------------------------------

    public function test_reject_requisition_throws_when_not_found(): void
    {
        $repo = Mockery::mock(PurchaseRequisitionRepositoryInterface::class);
        $repo->shouldReceive('findById')->andReturn(null);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());

        $useCase = new RejectPurchaseRequisitionUseCase($repo);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/not found/i');

        $useCase->execute('missing-id');
    }

    public function test_reject_requisition_throws_when_status_invalid(): void
    {
        $repo = Mockery::mock(PurchaseRequisitionRepositoryInterface::class);
        $repo->shouldReceive('findById')->andReturn($this->makeRequisition('approved'));

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());

        $useCase = new RejectPurchaseRequisitionUseCase($repo);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/only draft or pending_approval/i');

        $useCase->execute('pr-uuid-1');
    }

    public function test_reject_requisition_transitions_and_dispatches_event(): void
    {
        $draft    = $this->makeRequisition('pending_approval');
        $rejected = (object) array_merge(
            (array) $draft,
            ['status' => 'rejected', 'rejected_by' => null, 'rejection_reason' => 'Budget exceeded.']
        );

        $repo = Mockery::mock(PurchaseRequisitionRepositoryInterface::class);
        $repo->shouldReceive('findById')->andReturn($draft);
        $repo->shouldReceive('update')
            ->once()
            ->withArgs(fn ($id, $data) => $data['status'] === 'rejected'
                && $data['rejection_reason'] === 'Budget exceeded.')
            ->andReturn($rejected);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')
            ->once()
            ->withArgs(fn ($event) => $event instanceof PurchaseRequisitionRejected
                && $event->requisitionId === 'pr-uuid-1'
                && $event->reason === 'Budget exceeded.');

        $useCase = new RejectPurchaseRequisitionUseCase($repo);
        $result  = $useCase->execute('pr-uuid-1', 'Budget exceeded.');

        $this->assertSame('rejected', $result->status);
    }

    // -------------------------------------------------------------------------
    // ConvertRequisitionToPurchaseOrderUseCase
    // -------------------------------------------------------------------------

    public function test_convert_requisition_throws_when_not_found(): void
    {
        $prRepo = Mockery::mock(PurchaseRequisitionRepositoryInterface::class);
        $poRepo = Mockery::mock(PurchaseOrderRepositoryInterface::class);

        $prRepo->shouldReceive('findById')->andReturn(null);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());

        $useCase = new ConvertRequisitionToPurchaseOrderUseCase($prRepo, $poRepo);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/not found/i');

        $useCase->execute('missing-id', []);
    }

    public function test_convert_requisition_throws_when_not_approved(): void
    {
        $prRepo = Mockery::mock(PurchaseRequisitionRepositoryInterface::class);
        $poRepo = Mockery::mock(PurchaseOrderRepositoryInterface::class);

        $prRepo->shouldReceive('findById')->andReturn($this->makeRequisition('draft'));

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());

        $useCase = new ConvertRequisitionToPurchaseOrderUseCase($prRepo, $poRepo);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/only approved/i');

        $useCase->execute('pr-uuid-1', []);
    }

    public function test_convert_approved_requisition_creates_po_and_dispatches_event(): void
    {
        $pr = $this->makeRequisition('approved');
        $po = (object) [
            'id'     => 'po-uuid-1',
            'status' => 'draft',
            'total'  => '200.00000000',
        ];

        $prRepo = Mockery::mock(PurchaseRequisitionRepositoryInterface::class);
        $poRepo = Mockery::mock(PurchaseOrderRepositoryInterface::class);

        $prRepo->shouldReceive('findById')->andReturn($pr);
        $poRepo->shouldReceive('nextNumber')->andReturn('PO-2026-000001');
        $poRepo->shouldReceive('create')
            ->once()
            ->withArgs(fn ($data) => $data['status'] === 'draft'
                && $data['requisition_id'] === 'pr-uuid-1')
            ->andReturn($po);
        $prRepo->shouldReceive('update')
            ->once()
            ->withArgs(fn ($id, $data) => $data['status'] === 'po_raised');

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')
            ->once()
            ->withArgs(fn ($event) => $event instanceof PurchaseOrderCreated
                && $event->purchaseOrderId === 'po-uuid-1');

        $useCase = new ConvertRequisitionToPurchaseOrderUseCase($prRepo, $poRepo);
        $result  = $useCase->execute('pr-uuid-1', [
            'vendor_id' => 'vendor-uuid-1',
            'lines'     => [
                ['qty' => '2', 'unit_price' => '100', 'tax_rate' => '0'],
            ],
        ]);

        $this->assertSame('po-uuid-1', $result->id);
    }
}
