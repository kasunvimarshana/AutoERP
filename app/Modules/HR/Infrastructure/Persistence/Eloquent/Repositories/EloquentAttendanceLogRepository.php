<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\HR\Domain\Entities\AttendanceLog;
use Modules\HR\Domain\RepositoryInterfaces\AttendanceLogRepositoryInterface;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\AttendanceLogModel;

class EloquentAttendanceLogRepository extends EloquentRepository implements AttendanceLogRepositoryInterface
{
    public function __construct(AttendanceLogModel $model)
    {
        parent::__construct($model);
        $this->setDomainEntityMapper(fn (AttendanceLogModel $m): AttendanceLog => $this->mapModelToDomainEntity($m));
    }

    public function save(AttendanceLog $entity): AttendanceLog
    {
        $data = ['tenant_id' => $entity->getTenantId(), 'employee_id' => $entity->getEmployeeId(), 'biometric_device_id' => $entity->getBiometricDeviceId(), 'punch_time' => $entity->getPunchTime()->format('Y-m-d H:i:s'), 'punch_type' => $entity->getPunchType(), 'source' => $entity->getSource(), 'raw_data' => $entity->getRawData(), 'processed_at' => $entity->getProcessedAt()?->format('Y-m-d H:i:s')];
        $model = $entity->getId() ? $this->update($entity->getId(), $data) : $this->create($data);

        return $this->toDomainEntity($model);
    }

    public function find(int|string $id, array $columns = ['*']): ?AttendanceLog
    {
        return parent::find($id, $columns);
    }

    public function findByEmployeeAndDate(int $tenantId, int $employeeId, string $date): array
    {
        return $this->model->where('tenant_id', $tenantId)->where('employee_id', $employeeId)->whereDate('punch_time', $date)->get()->map(fn ($m) => $this->toDomainEntity($m))->all();
    }

    private function mapModelToDomainEntity(AttendanceLogModel $m): AttendanceLog
    {
        $pat = $m->processed_at instanceof \DateTimeInterface ? $m->processed_at : ($m->processed_at ? new \DateTimeImmutable($m->processed_at) : null);

        return new AttendanceLog($m->tenant_id, $m->employee_id, $m->biometric_device_id, $m->punch_time instanceof \DateTimeInterface ? $m->punch_time : new \DateTimeImmutable($m->punch_time), $m->punch_type, $m->source ?? 'biometric', $m->raw_data ?? [], $pat, $m->created_at instanceof \DateTimeInterface ? $m->created_at : new \DateTimeImmutable($m->created_at ?? 'now'), $m->updated_at instanceof \DateTimeInterface ? $m->updated_at : new \DateTimeImmutable($m->updated_at ?? 'now'), $m->id);
    }
}
