<?php

namespace Tests\Unit\HR;

use Carbon\Carbon;
use Mockery;
use Modules\HR\Application\Listeners\HandleLeaveRequestApprovedListener;
use Modules\HR\Application\UseCases\RecordLeaveAbsenceUseCase;
use Modules\Leave\Domain\Events\LeaveRequestApproved;
use PHPUnit\Framework\TestCase;


class LeaveRequestApprovedAttendanceListenerTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function makeEvent(
        string $leaveRequestId = 'req-1',
        string $tenantId       = 'tenant-1',
        string $approverId     = 'approver-1',
        string $employeeId     = 'emp-1',
        string $startDate      = '2026-03-10',
        string $endDate        = '2026-03-12',
        string $leaveTypeName  = 'Annual Leave',
    ): LeaveRequestApproved {
        return new LeaveRequestApproved(
            leaveRequestId: $leaveRequestId,
            tenantId:       $tenantId,
            approverId:     $approverId,
            employeeId:     $employeeId,
            startDate:      $startDate,
            endDate:        $endDate,
            leaveTypeName:  $leaveTypeName,
        );
    }

    // -------------------------------------------------------------------------
    // Guard: skip when tenantId is empty
    // -------------------------------------------------------------------------

    public function test_skips_when_tenant_id_empty(): void
    {
        $useCase = Mockery::mock(RecordLeaveAbsenceUseCase::class);
        $useCase->shouldNotReceive('execute');

        $event = $this->makeEvent(tenantId: '');

        (new HandleLeaveRequestApprovedListener($useCase))->handle($event);

        $this->addToAssertionCount(1);
    }

    // -------------------------------------------------------------------------
    // Guard: skip when employeeId is empty
    // -------------------------------------------------------------------------

    public function test_skips_when_employee_id_empty(): void
    {
        $useCase = Mockery::mock(RecordLeaveAbsenceUseCase::class);
        $useCase->shouldNotReceive('execute');

        $event = $this->makeEvent(employeeId: '');

        (new HandleLeaveRequestApprovedListener($useCase))->handle($event);

        $this->addToAssertionCount(1);
    }

    // -------------------------------------------------------------------------
    // Guard: skip when startDate is empty
    // -------------------------------------------------------------------------

    public function test_skips_when_start_date_empty(): void
    {
        $useCase = Mockery::mock(RecordLeaveAbsenceUseCase::class);
        $useCase->shouldNotReceive('execute');

        $event = $this->makeEvent(startDate: '');

        (new HandleLeaveRequestApprovedListener($useCase))->handle($event);

        $this->addToAssertionCount(1);
    }

    // -------------------------------------------------------------------------
    // Guard: skip when endDate is empty
    // -------------------------------------------------------------------------

    public function test_skips_when_end_date_empty(): void
    {
        $useCase = Mockery::mock(RecordLeaveAbsenceUseCase::class);
        $useCase->shouldNotReceive('execute');

        $event = $this->makeEvent(endDate: '');

        (new HandleLeaveRequestApprovedListener($useCase))->handle($event);

        $this->addToAssertionCount(1);
    }

    // -------------------------------------------------------------------------
    // Happy path: calls RecordLeaveAbsenceUseCase with correct data
    // -------------------------------------------------------------------------

    public function test_calls_use_case_with_correct_tenant_and_employee(): void
    {
        $useCase = Mockery::mock(RecordLeaveAbsenceUseCase::class);

        $useCase->shouldReceive('execute')
            ->once()
            ->with(Mockery::on(function (array $data) {
                return $data['tenant_id']   === 'tenant-1'
                    && $data['employee_id'] === 'emp-1';
            }))
            ->andReturn(3);

        $event = $this->makeEvent();

        (new HandleLeaveRequestApprovedListener($useCase))->handle($event);

        $this->addToAssertionCount(1);
    }

    // -------------------------------------------------------------------------
    // Happy path: leave dates are passed correctly
    // -------------------------------------------------------------------------

    public function test_calls_use_case_with_correct_dates(): void
    {
        $useCase = Mockery::mock(RecordLeaveAbsenceUseCase::class);

        $useCase->shouldReceive('execute')
            ->once()
            ->with(Mockery::on(function (array $data) {
                return $data['start_date'] === '2026-03-10'
                    && $data['end_date']   === '2026-03-12';
            }))
            ->andReturn(3);

        $event = $this->makeEvent(startDate: '2026-03-10', endDate: '2026-03-12');

        (new HandleLeaveRequestApprovedListener($useCase))->handle($event);

        $this->addToAssertionCount(1);
    }

    // -------------------------------------------------------------------------
    // Happy path: leave type name is passed correctly
    // -------------------------------------------------------------------------

    public function test_calls_use_case_with_leave_type_name(): void
    {
        $useCase = Mockery::mock(RecordLeaveAbsenceUseCase::class);

        $useCase->shouldReceive('execute')
            ->once()
            ->with(Mockery::on(function (array $data) {
                return $data['leave_type_name'] === 'Sick Leave';
            }))
            ->andReturn(1);

        $event = $this->makeEvent(leaveTypeName: 'Sick Leave');

        (new HandleLeaveRequestApprovedListener($useCase))->handle($event);

        $this->addToAssertionCount(1);
    }

    // -------------------------------------------------------------------------
    // Graceful degradation: RecordLeaveAbsenceUseCase throws DomainException
    // -------------------------------------------------------------------------

    public function test_graceful_degradation_when_use_case_throws_domain_exception(): void
    {
        $useCase = Mockery::mock(RecordLeaveAbsenceUseCase::class);

        $useCase->shouldReceive('execute')
            ->once()
            ->andThrow(new \DomainException('Employee not found.'));

        $event = $this->makeEvent();

        // Must not throw — graceful degradation.
        (new HandleLeaveRequestApprovedListener($useCase))->handle($event);

        $this->addToAssertionCount(1);
    }

    // -------------------------------------------------------------------------
    // Graceful degradation: RecordLeaveAbsenceUseCase throws RuntimeException
    // -------------------------------------------------------------------------

    public function test_graceful_degradation_when_use_case_throws_runtime_exception(): void
    {
        $useCase = Mockery::mock(RecordLeaveAbsenceUseCase::class);

        $useCase->shouldReceive('execute')
            ->once()
            ->andThrow(new \RuntimeException('DB connection error'));

        $event = $this->makeEvent();

        // Must not throw — graceful degradation.
        (new HandleLeaveRequestApprovedListener($useCase))->handle($event);

        $this->addToAssertionCount(1);
    }

    // -------------------------------------------------------------------------
    // Backwards compatibility: event with only required fields
    // -------------------------------------------------------------------------

    public function test_event_defaults_when_optional_fields_not_provided(): void
    {
        $event = new LeaveRequestApproved(
            leaveRequestId: 'req-legacy',
            tenantId:       'tenant-legacy',
            approverId:     'approver-legacy',
        );

        $this->assertSame('req-legacy', $event->leaveRequestId);
        $this->assertSame('tenant-legacy', $event->tenantId);
        $this->assertSame('approver-legacy', $event->approverId);
        $this->assertSame('', $event->employeeId);
        $this->assertSame('', $event->startDate);
        $this->assertSame('', $event->endDate);
        $this->assertSame('', $event->leaveTypeName);
    }

    // -------------------------------------------------------------------------
    // Event carries enriched fields when provided
    // -------------------------------------------------------------------------

    public function test_event_carries_enriched_fields(): void
    {
        $event = $this->makeEvent(
            employeeId:    'emp-99',
            startDate:     '2026-05-01',
            endDate:       '2026-05-05',
            leaveTypeName: 'Parental Leave',
        );

        $this->assertSame('emp-99', $event->employeeId);
        $this->assertSame('2026-05-01', $event->startDate);
        $this->assertSame('2026-05-05', $event->endDate);
        $this->assertSame('Parental Leave', $event->leaveTypeName);
    }
}
