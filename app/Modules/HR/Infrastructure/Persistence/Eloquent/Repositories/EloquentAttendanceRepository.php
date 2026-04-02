<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Persistence\Eloquent\Repositories;

use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\HR\Domain\Entities\Attendance;
use Modules\HR\Domain\RepositoryInterfaces\AttendanceRepositoryInterface;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\AttendanceModel;

class EloquentAttendanceRepository extends EloquentRepository implements AttendanceRepositoryInterface
{
    public function __construct(AttendanceModel $model)
    {
        parent::__construct($model);
        $this->setDomainEntityMapper(fn (AttendanceModel $model): Attendance => $this->mapModelToDomainEntity($model));
    }

    public function save(Attendance $attendance): Attendance
    {
        $savedModel = null;

        DB::transaction(function () use ($attendance, &$savedModel) {
            $data = [
                'tenant_id'      => $attendance->getTenantId(),
                'employee_id'    => $attendance->getEmployeeId(),
                'date'           => $attendance->getDate(),
                'check_in_time'  => $attendance->getCheckInTime()->format('H:i:s'),
                'check_out_time' => $attendance->getCheckOutTime()?->format('H:i:s'),
                'status'         => $attendance->getStatus(),
                'notes'          => $attendance->getNotes(),
                'hours_worked'   => $attendance->getHoursWorked(),
            ];

            if ($attendance->getId()) {
                $savedModel = $this->update($attendance->getId(), $data);
            } else {
                $savedModel = $this->model->create($data);
            }
        });

        if (! $savedModel instanceof AttendanceModel) {
            throw new \RuntimeException('Failed to save attendance.');
        }

        return $this->mapModelToDomainEntity($savedModel);
    }

    public function getByEmployee(int $employeeId): array
    {
        return $this->model->where('employee_id', $employeeId)
            ->get()
            ->map(fn ($m) => $this->mapModelToDomainEntity($m))
            ->all();
    }

    private function mapModelToDomainEntity(AttendanceModel $model): Attendance
    {
        $attendance = new Attendance(
            tenantId:    $model->tenant_id,
            employeeId:  $model->employee_id,
            date:        $model->date instanceof \DateTimeInterface ? $model->date->format('Y-m-d') : (string) $model->date,
            checkInTime: new DateTimeImmutable($model->date instanceof \DateTimeInterface ? $model->date->format('Y-m-d') . ' ' . $model->check_in_time : $model->date . ' ' . $model->check_in_time),
            status:      $model->status,
            notes:       $model->notes,
            hoursWorked: $model->hours_worked !== null ? (float) $model->hours_worked : null,
            id:          $model->id,
            createdAt:   $model->created_at ? new DateTimeImmutable($model->created_at instanceof \DateTimeInterface ? $model->created_at->format('Y-m-d H:i:s') : $model->created_at) : null,
            updatedAt:   $model->updated_at ? new DateTimeImmutable($model->updated_at instanceof \DateTimeInterface ? $model->updated_at->format('Y-m-d H:i:s') : $model->updated_at) : null,
        );

        if ($model->check_out_time !== null) {
            $dateStr = $model->date instanceof \DateTimeInterface ? $model->date->format('Y-m-d') : (string) $model->date;
            $attendance->checkOut(
                new DateTimeImmutable($dateStr . ' ' . $model->check_out_time),
                $model->hours_worked !== null ? (float) $model->hours_worked : null
            );
        }

        return $attendance;
    }
}
