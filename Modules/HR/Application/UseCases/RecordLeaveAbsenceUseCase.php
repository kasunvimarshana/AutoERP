<?php

namespace Modules\HR\Application\UseCases;

use Carbon\Carbon;
use DomainException;
use Illuminate\Support\Facades\DB;
use Modules\HR\Domain\Contracts\AttendanceRecordRepositoryInterface;
use Modules\HR\Domain\Contracts\EmployeeRepositoryInterface;


class RecordLeaveAbsenceUseCase
{
    /** Maximum days that can be processed in a single call (safety cap). */
    private const MAX_DAYS = 365;

    public function __construct(
        private AttendanceRecordRepositoryInterface $attendanceRepo,
        private EmployeeRepositoryInterface         $employeeRepo,
    ) {}

    /**
     * @param array{
     *   tenant_id:       string,
     *   employee_id:     string,
     *   start_date:      string,
     *   end_date:        string,
     *   leave_type_name: string,
     *   notes:           string|null,
     * } $data
     * @return int Number of absence records created.
     */
    public function execute(array $data): int
    {
        return DB::transaction(function () use ($data) {
            $tenantId      = (string) ($data['tenant_id'] ?? '');
            $employeeId    = (string) ($data['employee_id'] ?? '');
            $startDateStr  = (string) ($data['start_date'] ?? '');
            $endDateStr    = (string) ($data['end_date'] ?? '');
            $leaveTypeName = (string) ($data['leave_type_name'] ?? '');
            $notes         = isset($data['notes']) ? (string) $data['notes'] : null;

            if ($employeeId === '') {
                throw new DomainException('Employee ID is required.');
            }

            $employee = $this->employeeRepo->findById($employeeId);
            if ($employee === null) {
                throw new DomainException('Employee not found.');
            }

            if ($startDateStr === '' || $endDateStr === '') {
                throw new DomainException('Both start_date and end_date are required.');
            }

            $startDate = Carbon::parse($startDateStr)->startOfDay();
            $endDate   = Carbon::parse($endDateStr)->startOfDay();

            if ($endDate->lt($startDate)) {
                throw new DomainException('end_date must not be before start_date.');
            }

            $defaultNote = $leaveTypeName !== ''
                ? 'On leave: ' . $leaveTypeName
                : 'On leave';

            $created  = 0;
            $current  = $startDate->copy();
            $dayCount = 0;

            while ($current->lte($endDate) && $dayCount < self::MAX_DAYS) {
                $workDate = $current->toDateString();

                // Skip days that already have an open check-in record to avoid
                // creating duplicate attendance entries on event replay.
                $existingCheckIn = $this->attendanceRepo->findOpenCheckIn(
                    $tenantId,
                    $employeeId,
                    $workDate,
                );

                if ($existingCheckIn === null) {
                    $this->attendanceRepo->create([
                        'tenant_id'      => $tenantId,
                        'employee_id'    => $employeeId,
                        'work_date'      => $workDate,
                        'check_in'       => null,
                        'check_out'      => null,
                        'duration_hours' => null,
                        'status'         => 'on_leave',
                        'notes'          => $notes ?? $defaultNote,
                    ]);

                    $created++;
                }

                $current->addDay();
                $dayCount++;
            }

            return $created;
        });
    }
}
