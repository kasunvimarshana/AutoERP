<?php

namespace Tests\Unit\Leave;

use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Mockery;
use Modules\Leave\Application\UseCases\ApproveLeaveAllocationUseCase;
use Modules\Leave\Application\UseCases\CreateLeaveAllocationUseCase;
use Modules\Leave\Application\UseCases\DeductLeaveAllocationUseCase;
use Modules\Leave\Application\UseCases\RequestLeaveUseCase;
use Modules\Leave\Domain\Contracts\LeaveAllocationRepositoryInterface;
use Modules\Leave\Domain\Contracts\LeaveRequestRepositoryInterface;
use Modules\Leave\Domain\Contracts\LeaveTypeRepositoryInterface;
use Modules\Leave\Domain\Events\LeaveAllocated;
use Modules\Leave\Domain\Events\LeaveAllocationApproved;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Leave Allocation use cases.
 *
 * Covers allocation creation (BCMath validation), approval lifecycle,
 * balance deduction guards, and RequestLeaveUseCase balance check integration.
 */
class LeaveAllocationUseCaseTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function makeLeaveType(bool $isActive = true): object
    {
        return (object) [
            'id'        => 'type-uuid-1',
            'tenant_id' => 'tenant-uuid-1',
            'name'      => 'Annual Leave',
            'is_active' => $isActive,
        ];
    }

    private function makeAllocation(string $status = 'draft', string $totalDays = '20.00', string $usedDays = '0.00'): object
    {
        return (object) [
            'id'            => 'alloc-uuid-1',
            'tenant_id'     => 'tenant-uuid-1',
            'employee_id'   => 'emp-uuid-1',
            'leave_type_id' => 'type-uuid-1',
            'total_days'    => $totalDays,
            'used_days'     => $usedDays,
            'status'        => $status,
        ];
    }

    // -------------------------------------------------------------------------
    // CreateLeaveAllocationUseCase
    // -------------------------------------------------------------------------

    public function test_create_allocation_throws_when_leave_type_not_found(): void
    {
        $leaveTypeRepo  = Mockery::mock(LeaveTypeRepositoryInterface::class);
        $allocationRepo = Mockery::mock(LeaveAllocationRepositoryInterface::class);

        $leaveTypeRepo->shouldReceive('findById')->andReturn(null);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')->never();

        $useCase = new CreateLeaveAllocationUseCase($allocationRepo, $leaveTypeRepo);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessageMatches('/not found/i');

        $useCase->execute([
            'tenant_id'    => 'tenant-uuid-1',
            'employee_id'  => 'emp-uuid-1',
            'leave_type_id' => 'missing-type',
            'total_days'   => 10,
        ]);
    }

    public function test_create_allocation_throws_when_leave_type_inactive(): void
    {
        $leaveTypeRepo  = Mockery::mock(LeaveTypeRepositoryInterface::class);
        $allocationRepo = Mockery::mock(LeaveAllocationRepositoryInterface::class);

        $leaveTypeRepo->shouldReceive('findById')->andReturn($this->makeLeaveType(false));

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')->never();

        $useCase = new CreateLeaveAllocationUseCase($allocationRepo, $leaveTypeRepo);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessageMatches('/not active/i');

        $useCase->execute([
            'tenant_id'    => 'tenant-uuid-1',
            'employee_id'  => 'emp-uuid-1',
            'leave_type_id' => 'type-uuid-1',
            'total_days'   => 10,
        ]);
    }

    public function test_create_allocation_throws_when_total_days_is_zero(): void
    {
        $leaveTypeRepo  = Mockery::mock(LeaveTypeRepositoryInterface::class);
        $allocationRepo = Mockery::mock(LeaveAllocationRepositoryInterface::class);

        $leaveTypeRepo->shouldReceive('findById')->andReturn($this->makeLeaveType());

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')->never();

        $useCase = new CreateLeaveAllocationUseCase($allocationRepo, $leaveTypeRepo);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessageMatches('/greater than zero/i');

        $useCase->execute([
            'tenant_id'    => 'tenant-uuid-1',
            'employee_id'  => 'emp-uuid-1',
            'leave_type_id' => 'type-uuid-1',
            'total_days'   => 0,
        ]);
    }

    public function test_create_allocation_sets_draft_status_and_dispatches_event(): void
    {
        $allocation = $this->makeAllocation('draft', '20.00', '0.00');

        $leaveTypeRepo  = Mockery::mock(LeaveTypeRepositoryInterface::class);
        $allocationRepo = Mockery::mock(LeaveAllocationRepositoryInterface::class);

        $leaveTypeRepo->shouldReceive('findById')->andReturn($this->makeLeaveType());
        $allocationRepo->shouldReceive('create')
            ->once()
            ->withArgs(fn ($data) => $data['status'] === 'draft'
                && $data['used_days'] === '0.00'
                && $data['total_days'] === '20.00')
            ->andReturn($allocation);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')
            ->once()
            ->withArgs(fn ($event) => $event instanceof LeaveAllocated
                && $event->totalDays === '20.00');

        $useCase = new CreateLeaveAllocationUseCase($allocationRepo, $leaveTypeRepo);
        $result  = $useCase->execute([
            'tenant_id'    => 'tenant-uuid-1',
            'employee_id'  => 'emp-uuid-1',
            'leave_type_id' => 'type-uuid-1',
            'total_days'   => 20,
        ]);

        $this->assertSame('draft', $result->status);
        $this->assertSame('20.00', $result->total_days);
    }

    // -------------------------------------------------------------------------
    // ApproveLeaveAllocationUseCase
    // -------------------------------------------------------------------------

    public function test_approve_allocation_throws_when_not_found(): void
    {
        $allocationRepo = Mockery::mock(LeaveAllocationRepositoryInterface::class);
        $allocationRepo->shouldReceive('findById')->andReturn(null);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')->never();

        $useCase = new ApproveLeaveAllocationUseCase($allocationRepo);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessageMatches('/not found/i');

        $useCase->execute('missing-id', 'approver-uuid-1');
    }

    public function test_approve_allocation_throws_when_not_draft(): void
    {
        $allocationRepo = Mockery::mock(LeaveAllocationRepositoryInterface::class);
        $allocationRepo->shouldReceive('findById')->andReturn($this->makeAllocation('approved'));

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')->never();

        $useCase = new ApproveLeaveAllocationUseCase($allocationRepo);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessageMatches('/draft/i');

        $useCase->execute('alloc-uuid-1', 'approver-uuid-1');
    }

    public function test_approve_allocation_transitions_to_approved_and_dispatches_event(): void
    {
        $draft    = $this->makeAllocation('draft');
        $approved = (object) array_merge((array) $draft, ['status' => 'approved', 'approved_by' => 'approver-uuid-1']);

        $allocationRepo = Mockery::mock(LeaveAllocationRepositoryInterface::class);
        $allocationRepo->shouldReceive('findById')->andReturn($draft);
        $allocationRepo->shouldReceive('update')
            ->withArgs(fn ($id, $data) => $data['status'] === 'approved' && $data['approved_by'] === 'approver-uuid-1')
            ->once()
            ->andReturn($approved);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')
            ->once()
            ->withArgs(fn ($event) => $event instanceof LeaveAllocationApproved
                && $event->approvedBy === 'approver-uuid-1');

        $useCase = new ApproveLeaveAllocationUseCase($allocationRepo);
        $result  = $useCase->execute('alloc-uuid-1', 'approver-uuid-1');

        $this->assertSame('approved', $result->status);
    }

    // -------------------------------------------------------------------------
    // DeductLeaveAllocationUseCase
    // -------------------------------------------------------------------------

    public function test_deduct_throws_when_allocation_not_found(): void
    {
        $allocationRepo = Mockery::mock(LeaveAllocationRepositoryInterface::class);
        $allocationRepo->shouldReceive('findById')->andReturn(null);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());

        $useCase = new DeductLeaveAllocationUseCase($allocationRepo);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessageMatches('/not found/i');

        $useCase->execute('missing-id', '5.00');
    }

    public function test_deduct_throws_when_allocation_not_approved(): void
    {
        $allocationRepo = Mockery::mock(LeaveAllocationRepositoryInterface::class);
        $allocationRepo->shouldReceive('findById')->andReturn($this->makeAllocation('draft'));

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());

        $useCase = new DeductLeaveAllocationUseCase($allocationRepo);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessageMatches('/approved status/i');

        $useCase->execute('alloc-uuid-1', '5.00');
    }

    public function test_deduct_throws_when_insufficient_balance(): void
    {
        // total=20, used=18 → remaining=2; request 5 → should throw
        $allocationRepo = Mockery::mock(LeaveAllocationRepositoryInterface::class);
        $allocationRepo->shouldReceive('findById')->andReturn($this->makeAllocation('approved', '20.00', '18.00'));

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());

        $useCase = new DeductLeaveAllocationUseCase($allocationRepo);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessageMatches('/insufficient leave balance/i');

        $useCase->execute('alloc-uuid-1', '5.00');
    }

    public function test_deduct_updates_used_days_correctly(): void
    {
        // total=20, used=5 → remaining=15; request 3 → used becomes 8
        $allocation = $this->makeAllocation('approved', '20.00', '5.00');
        $updated    = (object) array_merge((array) $allocation, ['used_days' => '8.00']);

        $allocationRepo = Mockery::mock(LeaveAllocationRepositoryInterface::class);
        $allocationRepo->shouldReceive('findById')->andReturn($allocation);
        $allocationRepo->shouldReceive('update')
            ->withArgs(fn ($id, $data) => $data['used_days'] === '8.00')
            ->once()
            ->andReturn($updated);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());

        $useCase = new DeductLeaveAllocationUseCase($allocationRepo);
        $result  = $useCase->execute('alloc-uuid-1', '3.00');

        $this->assertSame('8.00', $result->used_days);
    }

    // -------------------------------------------------------------------------
    // RequestLeaveUseCase — balance check integration
    // -------------------------------------------------------------------------

    public function test_request_leave_throws_when_insufficient_allocation_balance(): void
    {
        // Allocation has 5 days total, 4 used → 1 remaining; request 3 days → should throw
        $allocation = $this->makeAllocation('approved', '5.00', '4.00');

        $leaveTypeRepo  = Mockery::mock(LeaveTypeRepositoryInterface::class);
        $requestRepo    = Mockery::mock(LeaveRequestRepositoryInterface::class);
        $allocationRepo = Mockery::mock(LeaveAllocationRepositoryInterface::class);

        $leaveTypeRepo->shouldReceive('findById')->andReturn($this->makeLeaveType());
        $allocationRepo->shouldReceive('findApprovedByEmployeeAndType')->andReturn($allocation);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());

        $useCase = new RequestLeaveUseCase($requestRepo, $leaveTypeRepo, $allocationRepo);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessageMatches('/insufficient leave balance/i');

        $useCase->execute([
            'tenant_id'      => 'tenant-uuid-1',
            'employee_id'    => 'emp-uuid-1',
            'leave_type_id'  => 'type-uuid-1',
            'start_date'     => '2026-03-01',
            'end_date'       => '2026-03-03',
            'days_requested' => 3,
        ]);
    }

    public function test_request_leave_succeeds_when_balance_sufficient(): void
    {
        // Allocation has 20 days total, 5 used → 15 remaining; request 5 → ok
        $allocation  = $this->makeAllocation('approved', '20.00', '5.00');
        $leaveRequest = (object) ['id' => 'req-uuid-1', 'status' => 'draft', 'days_requested' => 5];

        $leaveTypeRepo  = Mockery::mock(LeaveTypeRepositoryInterface::class);
        $requestRepo    = Mockery::mock(LeaveRequestRepositoryInterface::class);
        $allocationRepo = Mockery::mock(LeaveAllocationRepositoryInterface::class);

        $leaveTypeRepo->shouldReceive('findById')->andReturn($this->makeLeaveType());
        $allocationRepo->shouldReceive('findApprovedByEmployeeAndType')->andReturn($allocation);
        $requestRepo->shouldReceive('create')->once()->andReturn($leaveRequest);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());

        $useCase = new RequestLeaveUseCase($requestRepo, $leaveTypeRepo, $allocationRepo);
        $result  = $useCase->execute([
            'tenant_id'      => 'tenant-uuid-1',
            'employee_id'    => 'emp-uuid-1',
            'leave_type_id'  => 'type-uuid-1',
            'start_date'     => '2026-03-01',
            'end_date'       => '2026-03-05',
            'days_requested' => 5,
        ]);

        $this->assertSame('draft', $result->status);
    }
}
