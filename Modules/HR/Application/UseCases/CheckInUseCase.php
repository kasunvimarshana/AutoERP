<?php

namespace Modules\HR\Application\UseCases;

use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Modules\HR\Domain\Contracts\AttendanceRecordRepositoryInterface;
use Modules\HR\Domain\Contracts\EmployeeRepositoryInterface;
use Modules\HR\Domain\Events\AttendanceCheckedIn;

class CheckInUseCase
{
    public function __construct(
        private AttendanceRecordRepositoryInterface $attendanceRepo,
        private EmployeeRepositoryInterface         $employeeRepo,
    ) {}

    public function execute(array $data): object
    {
        return DB::transaction(function () use ($data) {
            $employee = $this->employeeRepo->findById($data['employee_id']);

            if ($employee === null) {
                throw new DomainException('Employee not found.');
            }

            $workDate = $data['work_date'] ?? date('Y-m-d');

            $existing = $this->attendanceRepo->findOpenCheckIn(
                $data['tenant_id'],
                $data['employee_id'],
                $workDate,
            );

            if ($existing !== null) {
                throw new DomainException('Employee already has an open check-in for this date.');
            }

            $checkIn = $data['check_in'] ?? now()->toDateTimeString();

            $record = $this->attendanceRepo->create([
                'tenant_id'   => $data['tenant_id'],
                'employee_id' => $data['employee_id'],
                'work_date'   => $workDate,
                'check_in'    => $checkIn,
                'check_out'   => null,
                'duration_hours' => null,
                'status'      => 'present',
                'notes'       => $data['notes'] ?? null,
            ]);

            Event::dispatch(new AttendanceCheckedIn(
                $record->id,
                $record->tenant_id,
                $record->employee_id,
                $checkIn,
            ));

            return $record;
        });
    }
}
