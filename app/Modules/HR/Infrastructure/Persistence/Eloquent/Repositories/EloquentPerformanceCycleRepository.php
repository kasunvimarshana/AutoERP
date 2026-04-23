<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\HR\Domain\Entities\PerformanceCycle;
use Modules\HR\Domain\RepositoryInterfaces\PerformanceCycleRepositoryInterface;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\PerformanceCycleModel;

class EloquentPerformanceCycleRepository extends EloquentRepository implements PerformanceCycleRepositoryInterface
{
    public function __construct(PerformanceCycleModel $model)
    {
        parent::__construct($model);
        $this->setDomainEntityMapper(fn (PerformanceCycleModel $m): PerformanceCycle => $this->map($m));
    }

    public function save(PerformanceCycle $e): PerformanceCycle
    {
        $data = ['tenant_id' => $e->getTenantId(), 'name' => $e->getName(), 'period_start' => $e->getPeriodStart()->format('Y-m-d'), 'period_end' => $e->getPeriodEnd()->format('Y-m-d'), 'is_active' => $e->isActive(), 'metadata' => $e->getMetadata()];
        $m = $e->getId() ? $this->update($e->getId(), $data) : $this->create($data);

        return $this->toDomainEntity($m);
    }

    public function find(int|string $id, array $columns = ['*']): ?PerformanceCycle
    {
        return parent::find($id, $columns);
    }

    private function map(PerformanceCycleModel $m): PerformanceCycle
    {
        $now = fn ($v) => $v instanceof \DateTimeInterface ? $v : new \DateTimeImmutable($v ?? 'now');

        return new PerformanceCycle($m->tenant_id, $m->name, $now($m->period_start), $now($m->period_end), (bool) $m->is_active, $m->metadata ?? [], $now($m->created_at), $now($m->updated_at), $m->id);
    }
}
