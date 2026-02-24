<?php

namespace Tests\Unit\HR;

use Carbon\Carbon;
use DomainException;
use Illuminate\Support\Facades\DB;
use Mockery;
use Modules\HR\Application\UseCases\RecordLeaveAbsenceUseCase;
use Modules\HR\Domain\Contracts\AttendanceRecordRepositoryInterface;
use Modules\HR\Domain\Contracts\EmployeeRepositoryInterface;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for RecordLeaveAbsenceUseCase.
 *
 * Verifies that on_leave absence attendance records are created for each
 * calendar day of a leave period, with correct guard behaviour and
 * idempotency against existing open check-ins.
 */
class RecordLeaveAbsenceUseCaseTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function makeEmployee(): object
    {
        return (object) ['id' => 'emp-1', 'tenant_id' => 'tenant-1'];
    }

    // -----------------------------------------------------------------------
    // Guard: employee not found
    // -----------------------------------------------------------------------

    public function test_throws_when_employee_not_found(): void
    {
        $employeeRepo   = Mockery::mock(EmployeeRepositoryInterface::class);
        $attendanceRepo = Mockery::mock(AttendanceRecordRepositoryInterface::class);

        $employeeRepo->shouldReceive('findById')->with('missing')->andReturn(null);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());

        $useCase = new RecordLeaveAbsenceUseCase($attendanceRepo, $employeeRepo);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessageMatches('/employee not found/i');

        $useCase->execute([
            'tenant_id'       => 'tenant-1',
            'employee_id'     => 'missing',
            'start_date'      => '2026-03-10',
            'end_date'        => '2026-03-10',
            'leave_type_name' => 'Annual Leave',
        ]);
    }

    // -----------------------------------------------------------------------
    // Guard: empty employee_id
    // -----------------------------------------------------------------------

    public function test_throws_when_employee_id_empty(): void
    {
        $employeeRepo   = Mockery::mock(EmployeeRepositoryInterface::class);
        $attendanceRepo = Mockery::mock(AttendanceRecordRepositoryInterface::class);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());

        $useCase = new RecordLeaveAbsenceUseCase($attendanceRepo, $employeeRepo);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessageMatches('/employee id is required/i');

        $useCase->execute([
            'tenant_id'   => 'tenant-1',
            'employee_id' => '',
            'start_date'  => '2026-03-10',
            'end_date'    => '2026-03-10',
        ]);
    }

    // -----------------------------------------------------------------------
    // Guard: missing start_date
    // -----------------------------------------------------------------------

    public function test_throws_when_start_date_empty(): void
    {
        $employeeRepo   = Mockery::mock(EmployeeRepositoryInterface::class);
        $attendanceRepo = Mockery::mock(AttendanceRecordRepositoryInterface::class);

        $employeeRepo->shouldReceive('findById')->andReturn($this->makeEmployee());

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());

        $useCase = new RecordLeaveAbsenceUseCase($attendanceRepo, $employeeRepo);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessageMatches('/start_date and end_date are required/i');

        $useCase->execute([
            'tenant_id'   => 'tenant-1',
            'employee_id' => 'emp-1',
            'start_date'  => '',
            'end_date'    => '2026-03-12',
        ]);
    }

    // -----------------------------------------------------------------------
    // Guard: end_date before start_date
    // -----------------------------------------------------------------------

    public function test_throws_when_end_date_before_start_date(): void
    {
        $employeeRepo   = Mockery::mock(EmployeeRepositoryInterface::class);
        $attendanceRepo = Mockery::mock(AttendanceRecordRepositoryInterface::class);

        $employeeRepo->shouldReceive('findById')->andReturn($this->makeEmployee());

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());

        $useCase = new RecordLeaveAbsenceUseCase($attendanceRepo, $employeeRepo);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessageMatches('/end_date must not be before start_date/i');

        $useCase->execute([
            'tenant_id'   => 'tenant-1',
            'employee_id' => 'emp-1',
            'start_date'  => '2026-03-12',
            'end_date'    => '2026-03-10',
        ]);
    }

    // -----------------------------------------------------------------------
    // Happy path: creates one record for a single-day leave
    // -----------------------------------------------------------------------

    public function test_creates_one_record_for_single_day_leave(): void
    {
        $employeeRepo   = Mockery::mock(EmployeeRepositoryInterface::class);
        $attendanceRepo = Mockery::mock(AttendanceRecordRepositoryInterface::class);

        $employeeRepo->shouldReceive('findById')->with('emp-1')->andReturn($this->makeEmployee());

        $attendanceRepo->shouldReceive('findOpenCheckIn')
            ->with('tenant-1', 'emp-1', '2026-03-10')
            ->andReturn(null);

        $attendanceRepo->shouldReceive('create')
            ->once()
            ->with(Mockery::on(function (array $d) {
                return $d['work_date'] === '2026-03-10'
                    && $d['status'] === 'on_leave'
                    && $d['check_in'] === null;
            }))
            ->andReturn((object) ['id' => 'att-1']);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());

        $useCase = new RecordLeaveAbsenceUseCase($attendanceRepo, $employeeRepo);

        $count = $useCase->execute([
            'tenant_id'       => 'tenant-1',
            'employee_id'     => 'emp-1',
            'start_date'      => '2026-03-10',
            'end_date'        => '2026-03-10',
            'leave_type_name' => 'Annual Leave',
        ]);

        $this->assertSame(1, $count);
    }

    // -----------------------------------------------------------------------
    // Happy path: creates 3 records for a 3-day leave
    // -----------------------------------------------------------------------

    public function test_creates_multiple_records_for_multi_day_leave(): void
    {
        $employeeRepo   = Mockery::mock(EmployeeRepositoryInterface::class);
        $attendanceRepo = Mockery::mock(AttendanceRecordRepositoryInterface::class);

        $employeeRepo->shouldReceive('findById')->andReturn($this->makeEmployee());

        $attendanceRepo->shouldReceive('findOpenCheckIn')
            ->times(3)
            ->andReturn(null);

        $attendanceRepo->shouldReceive('create')
            ->times(3)
            ->andReturn((object) ['id' => 'att-x']);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());

        $useCase = new RecordLeaveAbsenceUseCase($attendanceRepo, $employeeRepo);

        $count = $useCase->execute([
            'tenant_id'   => 'tenant-1',
            'employee_id' => 'emp-1',
            'start_date'  => '2026-03-10',
            'end_date'    => '2026-03-12',
        ]);

        $this->assertSame(3, $count);
    }

    // -----------------------------------------------------------------------
    // Idempotency: skips days that already have an open check-in
    // -----------------------------------------------------------------------

    public function test_skips_day_with_existing_open_check_in(): void
    {
        $employeeRepo   = Mockery::mock(EmployeeRepositoryInterface::class);
        $attendanceRepo = Mockery::mock(AttendanceRecordRepositoryInterface::class);

        $employeeRepo->shouldReceive('findById')->andReturn($this->makeEmployee());

        // Day 1: already has open check-in → skip
        $attendanceRepo->shouldReceive('findOpenCheckIn')
            ->with('tenant-1', 'emp-1', '2026-03-10')
            ->andReturn((object) ['id' => 'existing-att']);

        // Day 2: no existing record → create
        $attendanceRepo->shouldReceive('findOpenCheckIn')
            ->with('tenant-1', 'emp-1', '2026-03-11')
            ->andReturn(null);

        $attendanceRepo->shouldReceive('create')->once()
            ->andReturn((object) ['id' => 'att-new']);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());

        $useCase = new RecordLeaveAbsenceUseCase($attendanceRepo, $employeeRepo);

        $count = $useCase->execute([
            'tenant_id'   => 'tenant-1',
            'employee_id' => 'emp-1',
            'start_date'  => '2026-03-10',
            'end_date'    => '2026-03-11',
        ]);

        // Only one new record created (day 2); day 1 was skipped.
        $this->assertSame(1, $count);
    }

    // -----------------------------------------------------------------------
    // Note uses leave_type_name when provided
    // -----------------------------------------------------------------------

    public function test_note_uses_leave_type_name(): void
    {
        $employeeRepo   = Mockery::mock(EmployeeRepositoryInterface::class);
        $attendanceRepo = Mockery::mock(AttendanceRecordRepositoryInterface::class);

        $employeeRepo->shouldReceive('findById')->andReturn($this->makeEmployee());

        $attendanceRepo->shouldReceive('findOpenCheckIn')->andReturn(null);

        $attendanceRepo->shouldReceive('create')
            ->once()
            ->with(Mockery::on(function (array $d) {
                return str_contains((string) ($d['notes'] ?? ''), 'Sick Leave');
            }))
            ->andReturn((object) ['id' => 'att-1']);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());

        $useCase = new RecordLeaveAbsenceUseCase($attendanceRepo, $employeeRepo);

        $useCase->execute([
            'tenant_id'       => 'tenant-1',
            'employee_id'     => 'emp-1',
            'start_date'      => '2026-03-10',
            'end_date'        => '2026-03-10',
            'leave_type_name' => 'Sick Leave',
        ]);

        $this->addToAssertionCount(1);
    }
}
