<?php

namespace Tests\Unit\Leave;

use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Mockery;
use Modules\Leave\Application\UseCases\ApproveLeaveRequestUseCase;
use Modules\Leave\Application\UseCases\RejectLeaveRequestUseCase;
use Modules\Leave\Application\UseCases\RequestLeaveUseCase;
use Modules\Leave\Domain\Contracts\LeaveRequestRepositoryInterface;
use Modules\Leave\Domain\Contracts\LeaveTypeRepositoryInterface;
use Modules\Leave\Domain\Events\LeaveRequestApproved;
use Modules\Leave\Domain\Events\LeaveRequestRejected;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Leave Management use cases.
 *
 * Covers leave request creation with type guards,
 * approval/rejection status validation, and domain event dispatch.
 */
class LeaveUseCaseTest extends TestCase
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
            'max_days'  => 20,
            'is_paid'   => true,
            'is_active' => $isActive,
        ];
    }

    private function makeLeaveRequest(string $status = 'draft'): object
    {
        return (object) [
            'id'             => 'req-uuid-1',
            'tenant_id'      => 'tenant-uuid-1',
            'employee_id'    => 'emp-uuid-1',
            'leave_type_id'  => 'type-uuid-1',
            'days_requested' => 5,
            'status'         => $status,
        ];
    }

    // -------------------------------------------------------------------------
    // RequestLeaveUseCase
    // -------------------------------------------------------------------------

    public function test_request_leave_throws_when_leave_type_not_found(): void
    {
        $leaveTypeRepo = Mockery::mock(LeaveTypeRepositoryInterface::class);
        $leaveTypeRepo->shouldReceive('findById')->andReturn(null);

        $requestRepo = Mockery::mock(LeaveRequestRepositoryInterface::class);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());

        $useCase = new RequestLeaveUseCase($requestRepo, $leaveTypeRepo);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessageMatches('/not found/i');

        $useCase->execute([
            'tenant_id'      => 'tenant-uuid-1',
            'employee_id'    => 'emp-uuid-1',
            'leave_type_id'  => 'missing-type',
            'start_date'     => '2026-03-01',
            'end_date'       => '2026-03-05',
            'days_requested' => 5,
        ]);
    }

    public function test_request_leave_throws_when_leave_type_inactive(): void
    {
        $leaveTypeRepo = Mockery::mock(LeaveTypeRepositoryInterface::class);
        $leaveTypeRepo->shouldReceive('findById')->andReturn($this->makeLeaveType(false));

        $requestRepo = Mockery::mock(LeaveRequestRepositoryInterface::class);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());

        $useCase = new RequestLeaveUseCase($requestRepo, $leaveTypeRepo);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessageMatches('/not active/i');

        $useCase->execute([
            'tenant_id'      => 'tenant-uuid-1',
            'employee_id'    => 'emp-uuid-1',
            'leave_type_id'  => 'type-uuid-1',
            'start_date'     => '2026-03-01',
            'end_date'       => '2026-03-05',
            'days_requested' => 5,
        ]);
    }

    public function test_request_leave_creates_draft_request(): void
    {
        $leaveRequest = $this->makeLeaveRequest('draft');

        $leaveTypeRepo = Mockery::mock(LeaveTypeRepositoryInterface::class);
        $leaveTypeRepo->shouldReceive('findById')->andReturn($this->makeLeaveType());

        $requestRepo = Mockery::mock(LeaveRequestRepositoryInterface::class);
        $requestRepo->shouldReceive('create')
            ->once()
            ->withArgs(fn ($data) => $data['status'] === 'draft' && $data['days_requested'] === 5)
            ->andReturn($leaveRequest);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());

        $useCase = new RequestLeaveUseCase($requestRepo, $leaveTypeRepo);
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

    // -------------------------------------------------------------------------
    // ApproveLeaveRequestUseCase
    // -------------------------------------------------------------------------

    public function test_approve_throws_when_request_not_found(): void
    {
        $requestRepo = Mockery::mock(LeaveRequestRepositoryInterface::class);
        $requestRepo->shouldReceive('findById')->andReturn(null);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')->never();

        $useCase = new ApproveLeaveRequestUseCase($requestRepo);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessageMatches('/not found/i');

        $useCase->execute('missing-id', 'approver-uuid-1');
    }

    public function test_approve_throws_when_request_already_approved(): void
    {
        $requestRepo = Mockery::mock(LeaveRequestRepositoryInterface::class);
        $requestRepo->shouldReceive('findById')->andReturn($this->makeLeaveRequest('approved'));

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')->never();

        $useCase = new ApproveLeaveRequestUseCase($requestRepo);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessageMatches('/draft/i');

        $useCase->execute('req-uuid-1', 'approver-uuid-1');
    }

    public function test_approve_transitions_to_approved_and_dispatches_event(): void
    {
        $request  = $this->makeLeaveRequest('draft');
        $approved = (object) array_merge((array) $request, [
            'status'      => 'approved',
            'reviewer_id' => 'approver-uuid-1',
        ]);

        $requestRepo = Mockery::mock(LeaveRequestRepositoryInterface::class);
        $requestRepo->shouldReceive('findById')->andReturn($request);
        $requestRepo->shouldReceive('update')
            ->withArgs(fn ($id, $data) => $data['status'] === 'approved')
            ->once()
            ->andReturn($approved);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')
            ->once()
            ->withArgs(fn ($event) => $event instanceof LeaveRequestApproved
                && $event->approverId === 'approver-uuid-1');

        $useCase = new ApproveLeaveRequestUseCase($requestRepo);
        $result  = $useCase->execute('req-uuid-1', 'approver-uuid-1');

        $this->assertSame('approved', $result->status);
    }

    // -------------------------------------------------------------------------
    // RejectLeaveRequestUseCase
    // -------------------------------------------------------------------------

    public function test_reject_throws_when_request_not_found(): void
    {
        $requestRepo = Mockery::mock(LeaveRequestRepositoryInterface::class);
        $requestRepo->shouldReceive('findById')->andReturn(null);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')->never();

        $useCase = new RejectLeaveRequestUseCase($requestRepo);

        $this->expectException(DomainException::class);

        $useCase->execute('missing-id', 'reviewer-uuid-1');
    }

    public function test_reject_transitions_to_rejected_and_dispatches_event(): void
    {
        $request  = $this->makeLeaveRequest('draft');
        $rejected = (object) array_merge((array) $request, ['status' => 'rejected']);

        $requestRepo = Mockery::mock(LeaveRequestRepositoryInterface::class);
        $requestRepo->shouldReceive('findById')->andReturn($request);
        $requestRepo->shouldReceive('update')
            ->withArgs(fn ($id, $data) => $data['status'] === 'rejected')
            ->once()
            ->andReturn($rejected);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')
            ->once()
            ->withArgs(fn ($event) => $event instanceof LeaveRequestRejected
                && $event->reviewerId === 'reviewer-uuid-1');

        $useCase = new RejectLeaveRequestUseCase($requestRepo);
        $result  = $useCase->execute('req-uuid-1', 'reviewer-uuid-1', 'Insufficient balance');

        $this->assertSame('rejected', $result->status);
    }
}
