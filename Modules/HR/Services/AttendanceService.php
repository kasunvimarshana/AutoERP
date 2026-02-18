<?php

declare(strict_types=1);

namespace Modules\HR\Services;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Modules\Core\Services\BaseService;
use Modules\HR\Entities\Attendance;
use Modules\HR\Repositories\Contracts\AttendanceRepositoryInterface;
use Modules\HR\Repositories\Contracts\EmployeeRepositoryInterface;

/**
 * Attendance Service
 *
 * Handles business logic for attendance tracking and work hours calculation.
 */
class AttendanceService extends BaseService
{
    /**
     * AttendanceService constructor.
     */
    public function __construct(
        protected AttendanceRepositoryInterface $attendanceRepository,
        protected EmployeeRepositoryInterface $employeeRepository
    ) {}

    /**
     * Create attendance record.
     */
    public function create(array $data): Attendance
    {
        return $this->executeInTransaction(function () use ($data) {
            $attendance = $this->attendanceRepository->create($data);

            $this->logInfo('Attendance created', [
                'attendance_id' => $attendance->id,
                'employee_id' => $attendance->employee_id,
            ]);

            return $attendance;
        });
    }

    /**
     * Update attendance record.
     */
    public function update(int $id, array $data): Attendance
    {
        return $this->executeInTransaction(function () use ($id, $data) {
            $attendance = $this->attendanceRepository->update($id, $data);

            // Recalculate work hours if check-in or check-out changed
            if (isset($data['check_in']) || isset($data['check_out'])) {
                $workHours = $attendance->calculateWorkHours();
                if ($workHours !== null) {
                    $attendance->work_hours = $workHours;
                    $attendance->save();
                }
            }

            $this->logInfo('Attendance updated', ['attendance_id' => $attendance->id]);

            return $attendance;
        });
    }

    /**
     * Check-in employee.
     */
    public function checkIn(int $employeeId, ?Carbon $checkInTime = null): Attendance
    {
        return $this->executeInTransaction(function () use ($employeeId, $checkInTime) {
            $date = Carbon::today()->toDateString();
            $checkInTime = $checkInTime ?? Carbon::now();

            // Check if attendance already exists for today
            $attendance = $this->attendanceRepository->getByEmployeeAndDate($employeeId, $date);

            if ($attendance) {
                // Update existing attendance
                $attendance->check_in = $checkInTime;
                $attendance->save();
            } else {
                // Create new attendance
                $attendance = $this->attendanceRepository->create([
                    'employee_id' => $employeeId,
                    'date' => $date,
                    'check_in' => $checkInTime,
                    'status' => 'present',
                ]);
            }

            $this->logInfo('Employee checked in', [
                'employee_id' => $employeeId,
                'check_in' => $checkInTime->toDateTimeString(),
            ]);

            return $attendance;
        });
    }

    /**
     * Check-out employee.
     */
    public function checkOut(int $employeeId, ?Carbon $checkOutTime = null): Attendance
    {
        return $this->executeInTransaction(function () use ($employeeId, $checkOutTime) {
            $date = Carbon::today()->toDateString();
            $checkOutTime = $checkOutTime ?? Carbon::now();

            $attendance = $this->attendanceRepository->getByEmployeeAndDate($employeeId, $date);

            if (! $attendance) {
                throw new \RuntimeException('No check-in record found for today.');
            }

            if (! $attendance->check_in) {
                throw new \RuntimeException('Cannot check-out without check-in.');
            }

            $attendance->check_out = $checkOutTime;
            $attendance->work_hours = $attendance->calculateWorkHours();
            $attendance->save();

            $this->logInfo('Employee checked out', [
                'employee_id' => $employeeId,
                'check_out' => $checkOutTime->toDateTimeString(),
                'work_hours' => $attendance->work_hours,
            ]);

            return $attendance;
        });
    }

    /**
     * Get attendance by employee and date range.
     */
    public function getByEmployee(int $employeeId, ?string $startDate = null, ?string $endDate = null): Collection
    {
        return $this->attendanceRepository->getByEmployee($employeeId, $startDate, $endDate);
    }

    /**
     * Get attendance by date range.
     */
    public function getByDateRange(string $startDate, string $endDate): Collection
    {
        return $this->attendanceRepository->getByDateRange($startDate, $endDate);
    }

    /**
     * Calculate total work hours for an employee in a date range.
     */
    public function calculateTotalWorkHours(int $employeeId, string $startDate, string $endDate): float
    {
        $attendances = $this->attendanceRepository->getByEmployee($employeeId, $startDate, $endDate);

        return $attendances->sum('work_hours') ?? 0.0;
    }

    /**
     * Get incomplete attendance records (checked-in but not checked-out).
     */
    public function getIncomplete(): Collection
    {
        return $this->attendanceRepository->getIncomplete();
    }

    /**
     * Find attendance by ID.
     */
    public function findById(int $id): ?Attendance
    {
        return $this->attendanceRepository->find($id);
    }

    /**
     * Delete attendance record.
     */
    public function delete(int $id): bool
    {
        return $this->attendanceRepository->delete($id);
    }
}
