<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\HR\Domain\Entities\Shift;
use Modules\HR\Domain\RepositoryInterfaces\ShiftRepositoryInterface;
use Modules\HR\Domain\ValueObjects\ShiftType;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\ShiftModel;

class EloquentShiftRepository extends EloquentRepository implements ShiftRepositoryInterface
{
    public function __construct(ShiftModel $model)
    {
        parent::__construct($model);
        $this->setDomainEntityMapper(fn (ShiftModel $m): Shift => $this->mapModelToDomainEntity($m));
    }

    public function save(Shift $entity): Shift
    {
        $data = ['tenant_id' => $entity->getTenantId(), 'name' => $entity->getName(), 'code' => $entity->getCode(), 'shift_type' => $entity->getShiftType()->value, 'start_time' => $entity->getStartTime(), 'end_time' => $entity->getEndTime(), 'break_duration' => $entity->getBreakDuration(), 'work_days' => $entity->getWorkDays(), 'grace_minutes' => $entity->getGraceMinutes(), 'overtime_threshold' => $entity->getOvertimeThreshold(), 'is_night_shift' => $entity->isNightShift(), 'metadata' => $entity->getMetadata(), 'is_active' => $entity->isActive()];
            $data = ['tenant_id' => $entity->getTenantId(), 'name' => $entity->getName(), 'code' => $entity->getCode(), 'shift_type' => $entity->getShiftType()->value, 'start_time' => $entity->getStartTime(), 'end_time' => $entity->getEndTime(), 'break_duration' => $entity->getBreakDuration(), 'work_days' => $entity->getWorkDays(), 'grace_minutes' => $entity->getGraceMinutes(), 'overtime_threshold' => $entity->getOvertimeThreshold(), 'is_night_shift' => $entity->isNightShift(), 'metadata' => $entity->getMetadata(), 'is_active' => $entity->isActive(), 'row_version' => $entity->getRowVersion()];
        $model = $entity->getId() ? $this->update($entity->getId(), $data) : $this->create($data);

        return $this->toDomainEntity($model);
    }

    public function find(int|string $id, array $columns = ['*']): ?Shift
    {
        return parent::find($id, $columns);
    }

    public function findByTenantAndCode(int $tenantId, string $code): ?Shift
    {
        $m = $this->model->where('tenant_id', $tenantId)->where('code', $code)->first();

        return $m ? $this->toDomainEntity($m) : null;
    }

    private function mapModelToDomainEntity(ShiftModel $m): Shift
    {
        return new Shift($m->tenant_id, $m->name, $m->code, ShiftType::from($m->shift_type), $m->start_time, $m->end_time, (int) $m->break_duration, $m->work_days ?? [], (int) $m->grace_minutes, (int) $m->overtime_threshold, (bool) $m->is_night_shift, $m->metadata ?? [], (bool) $m->is_active, $m->created_at instanceof \DateTimeInterface ? $m->created_at : new \DateTimeImmutable($m->created_at ?? 'now'), $m->updated_at instanceof \DateTimeInterface ? $m->updated_at : new \DateTimeImmutable($m->updated_at ?? 'now'), $m->id);
        return new Shift($m->tenant_id, $m->name, $m->code, ShiftType::from($m->shift_type), $m->start_time, $m->end_time, (int) $m->break_duration, $m->work_days ?? [], (int) $m->grace_minutes, (int) $m->overtime_threshold, (bool) $m->is_night_shift, $m->metadata ?? [], (bool) $m->is_active, $m->created_at instanceof \DateTimeInterface ? $m->created_at : new \DateTimeImmutable($m->created_at ?? 'now'), $m->updated_at instanceof \DateTimeInterface ? $m->updated_at : new \DateTimeImmutable($m->updated_at ?? 'now'), (int) ($m->row_version ?? 1), $m->id);
    }
}
