<?php

namespace Modules\HR\Application\UseCases;

use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Modules\HR\Domain\Contracts\AttendanceRecordRepositoryInterface;
use Modules\HR\Domain\Events\AttendanceCheckedOut;

class CheckOutUseCase
{
    public function __construct(
        private AttendanceRecordRepositoryInterface $attendanceRepo,
    ) {}

    public function execute(array $data): object
    {
        return DB::transaction(function () use ($data) {
            $record = $this->attendanceRepo->findById($data['attendance_id']);

            if ($record === null) {
                throw new DomainException('Attendance record not found.');
            }

            if ($record->check_out !== null) {
                throw new DomainException('Attendance record already checked out.');
            }

            $checkOut = $data['check_out'] ?? now()->toDateTimeString();

            // BCMath duration in hours: (check_out - check_in) / 3600 seconds, scale 2
            $checkInTs  = strtotime($record->check_in);
            $checkOutTs = strtotime($checkOut);
            $diffSeconds = (string) max(0, $checkOutTs - $checkInTs);
            $durationHours = bcdiv($diffSeconds, '3600', 2);

            $updated = $this->attendanceRepo->update($data['attendance_id'], [
                'check_out'      => $checkOut,
                'duration_hours' => $durationHours,
            ]);

            Event::dispatch(new AttendanceCheckedOut(
                $record->id,
                $record->tenant_id,
                $record->employee_id,
                $checkOut,
                $durationHours,
            ));

            return $updated;
        });
    }
}
