<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\HR\Domain\Entities\AttendanceRecord;
use Modules\HR\Domain\RepositoryInterfaces\AttendanceRecordRepositoryInterface;
use Modules\HR\Domain\ValueObjects\AttendanceStatus;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\AttendanceRecordModel;

class EloquentAttendanceRecordRepository extends EloquentRepository implements AttendanceRecordRepositoryInterface
{
    public function __construct(AttendanceRecordModel $model)
    {
        parent::__construct($model);
        $this->setDomainEntityMapper(fn (AttendanceRecordModel $m): AttendanceRecord => $this->mapModelToDomainEntity($m));
    }

    public function save(AttendanceRecord $entity): AttendanceRecord
    {
        $data = ['tenant_id' => $entity->getTenantId(), 'employee_id' => $entity->getEmployeeId(), 'attendance_date' => $entity->getAttendanceDate()->format('Y-m-d'), 'check_in' => $entity->getCheckIn()?->format('Y-m-d H:i:s'), 'check_out' => $entity->getCheckOut()?->format('Y-m-d H:i:s'), 'break_duration' => $entity->getBreakDuration(), 'worked_minutes' => $entity->getWorkedMinutes(), 'overtime_minutes' => $entity->getOvertimeMinutes(), 'status' => $entity->getStatus()->value, 'shift_id' => $entity->getShiftId(), 'remarks' => $entity->getRemarks(), 'metadata' => $entity->getMetadata()];
        $model = $entity->getId() ? $this->update($entity->getId(), $data) : $this->create($data);

        return $this->toDomainEntity($model);
    }

    public function find(int|string $id, array $columns = ['*']): ?AttendanceRecord
    {
        return parent::find($id, $columns);
    }

    public function findByEmployeeAndDate(int $tenantId, int $employeeId, string $date): ?AttendanceRecord
    {
        $m = $this->model->where('tenant_id', $tenantId)->where('employee_id', $employeeId)->whereDate('attendance_date', $date)->first();

        return $m ? $this->toDomainEntity($m) : null;
    }

    public function findByEmployeeAndMonth(int $tenantId, int $employeeId, int $year, int $month): array
    {
        return $this->model->where('tenant_id', $tenantId)->where('employee_id', $employeeId)->whereYear('attendance_date', $year)->whereMonth('attendance_date', $month)->get()->map(fn ($m) => $this->toDomainEntity($m))->all();
    }

    private function mapModelToDomainEntity(AttendanceRecordModel $m): AttendanceRecord
    {
        $ci = $m->check_in instanceof \DateTimeInterface ? $m->check_in : ($m->check_in ? new \DateTimeImmutable($m->check_in) : null);
        $co = $m->check_out instanceof \DateTimeInterface ? $m->check_out : ($m->check_out ? new \DateTimeImmutable($m->check_out) : null);

        return new AttendanceRecord($m->tenant_id, $m->employee_id, $m->attendance_date instanceof \DateTimeInterface ? $m->attendance_date : new \DateTimeImmutable($m->attendance_date), $ci, $co, (int) $m->break_duration, (int) $m->worked_minutes, (int) $m->overtime_minutes, AttendanceStatus::from($m->status), $m->shift_id, $m->remarks ?? '', $m->metadata ?? [], $m->created_at instanceof \DateTimeInterface ? $m->created_at : new \DateTimeImmutable($m->created_at ?? 'now'), $m->updated_at instanceof \DateTimeInterface ? $m->updated_at : new \DateTimeImmutable($m->updated_at ?? 'now'), $m->id);
    }
}
