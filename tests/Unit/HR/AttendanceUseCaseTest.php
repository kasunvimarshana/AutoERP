<?php

namespace Tests\Unit\HR;

use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Mockery;
use Modules\HR\Application\UseCases\CheckInUseCase;
use Modules\HR\Application\UseCases\CheckOutUseCase;
use Modules\HR\Domain\Contracts\AttendanceRecordRepositoryInterface;
use Modules\HR\Domain\Contracts\EmployeeRepositoryInterface;
use Modules\HR\Domain\Events\AttendanceCheckedIn;
use Modules\HR\Domain\Events\AttendanceCheckedOut;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for HR Attendance use cases.
 *
 * Verifies check-in/check-out guards, BCMath duration calculation,
 * and that domain events are dispatched on success.
 */
class AttendanceUseCaseTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // -----------------------------------------------------------------------
    // CheckInUseCase
    // -----------------------------------------------------------------------

    public function test_check_in_throws_when_employee_not_found(): void
    {
        $employeeRepo   = Mockery::mock(EmployeeRepositoryInterface::class);
        $attendanceRepo = Mockery::mock(AttendanceRecordRepositoryInterface::class);

        $employeeRepo->shouldReceive('findById')->with('missing-emp')->andReturn(null);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')->never();

        $useCase = new CheckInUseCase($attendanceRepo, $employeeRepo);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessageMatches('/employee not found/i');

        $useCase->execute([
            'tenant_id'   => 'tenant-1',
            'employee_id' => 'missing-emp',
            'work_date'   => '2025-01-15',
        ]);
    }

    public function test_check_in_throws_when_open_check_in_exists(): void
    {
        $employeeRepo   = Mockery::mock(EmployeeRepositoryInterface::class);
        $attendanceRepo = Mockery::mock(AttendanceRecordRepositoryInterface::class);

        $employee = (object) ['id' => 'emp-1', 'tenant_id' => 'tenant-1'];
        $employeeRepo->shouldReceive('findById')->with('emp-1')->andReturn($employee);

        $existingRecord = (object) ['id' => 'att-existing', 'check_out' => null];
        $attendanceRepo->shouldReceive('findOpenCheckIn')
            ->with('tenant-1', 'emp-1', '2025-01-15')
            ->andReturn($existingRecord);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')->never();

        $useCase = new CheckInUseCase($attendanceRepo, $employeeRepo);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessageMatches('/open check-in/i');

        $useCase->execute([
            'tenant_id'   => 'tenant-1',
            'employee_id' => 'emp-1',
            'work_date'   => '2025-01-15',
        ]);
    }

    public function test_check_in_creates_record_and_dispatches_event(): void
    {
        $employeeRepo   = Mockery::mock(EmployeeRepositoryInterface::class);
        $attendanceRepo = Mockery::mock(AttendanceRecordRepositoryInterface::class);

        $employee = (object) ['id' => 'emp-1', 'tenant_id' => 'tenant-1'];
        $employeeRepo->shouldReceive('findById')->with('emp-1')->andReturn($employee);

        $attendanceRepo->shouldReceive('findOpenCheckIn')
            ->with('tenant-1', 'emp-1', '2025-01-15')
            ->andReturn(null);

        $record = (object) [
            'id'          => 'att-uuid-1',
            'tenant_id'   => 'tenant-1',
            'employee_id' => 'emp-1',
            'work_date'   => '2025-01-15',
            'check_in'    => '2025-01-15 09:00:00',
            'check_out'   => null,
            'status'      => 'present',
        ];
        $attendanceRepo->shouldReceive('create')->once()->andReturn($record);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')
            ->once()
            ->withArgs(fn ($e) => $e instanceof AttendanceCheckedIn
                && $e->attendanceId === 'att-uuid-1'
                && $e->employeeId  === 'emp-1');

        $useCase = new CheckInUseCase($attendanceRepo, $employeeRepo);
        $result  = $useCase->execute([
            'tenant_id'   => 'tenant-1',
            'employee_id' => 'emp-1',
            'work_date'   => '2025-01-15',
            'check_in'    => '2025-01-15 09:00:00',
        ]);

        $this->assertSame('present', $result->status);
        $this->assertNull($result->check_out);
    }

    // -----------------------------------------------------------------------
    // CheckOutUseCase
    // -----------------------------------------------------------------------

    public function test_check_out_throws_when_record_not_found(): void
    {
        $attendanceRepo = Mockery::mock(AttendanceRecordRepositoryInterface::class);
        $attendanceRepo->shouldReceive('findById')->with('missing-att')->andReturn(null);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')->never();

        $useCase = new CheckOutUseCase($attendanceRepo);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessageMatches('/attendance record not found/i');

        $useCase->execute(['attendance_id' => 'missing-att']);
    }

    public function test_check_out_throws_when_already_checked_out(): void
    {
        $attendanceRepo = Mockery::mock(AttendanceRecordRepositoryInterface::class);

        $record = (object) [
            'id'          => 'att-uuid-1',
            'tenant_id'   => 'tenant-1',
            'employee_id' => 'emp-1',
            'check_in'    => '2025-01-15 09:00:00',
            'check_out'   => '2025-01-15 17:00:00',
        ];
        $attendanceRepo->shouldReceive('findById')->with('att-uuid-1')->andReturn($record);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')->never();

        $useCase = new CheckOutUseCase($attendanceRepo);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessageMatches('/already checked out/i');

        $useCase->execute(['attendance_id' => 'att-uuid-1', 'check_out' => '2025-01-15 18:00:00']);
    }

    public function test_check_out_calculates_bcmath_duration_and_dispatches_event(): void
    {
        $attendanceRepo = Mockery::mock(AttendanceRecordRepositoryInterface::class);

        $record = (object) [
            'id'          => 'att-uuid-1',
            'tenant_id'   => 'tenant-1',
            'employee_id' => 'emp-1',
            'check_in'    => '2025-01-15 09:00:00',
            'check_out'   => null,
        ];
        $attendanceRepo->shouldReceive('findById')->with('att-uuid-1')->andReturn($record);

        // 8 hours = 28800 seconds → bcdiv('28800','3600',2) = '8.00'
        $updated = (object) array_merge((array) $record, [
            'check_out'      => '2025-01-15 17:00:00',
            'duration_hours' => '8.00',
        ]);
        $attendanceRepo->shouldReceive('update')
            ->once()
            ->withArgs(fn ($id, $data) => $id === 'att-uuid-1'
                && $data['check_out']      === '2025-01-15 17:00:00'
                && $data['duration_hours'] === '8.00')
            ->andReturn($updated);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')
            ->once()
            ->withArgs(fn ($e) => $e instanceof AttendanceCheckedOut
                && $e->durationHours === '8.00');

        $useCase = new CheckOutUseCase($attendanceRepo);
        $result  = $useCase->execute([
            'attendance_id' => 'att-uuid-1',
            'check_out'     => '2025-01-15 17:00:00',
        ]);

        $this->assertSame('8.00', $result->duration_hours);
    }
}
