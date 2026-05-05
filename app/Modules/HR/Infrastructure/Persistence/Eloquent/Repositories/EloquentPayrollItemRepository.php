<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\HR\Domain\Entities\PayrollItem;
use Modules\HR\Domain\RepositoryInterfaces\PayrollItemRepositoryInterface;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\PayrollItemModel;

class EloquentPayrollItemRepository extends EloquentRepository implements PayrollItemRepositoryInterface
{
    public function __construct(PayrollItemModel $model)
    {
        parent::__construct($model);
        $this->setDomainEntityMapper(fn (PayrollItemModel $m): PayrollItem => $this->map($m));
    }

    public function save(PayrollItem $e): PayrollItem
    {
        $data = ['tenant_id' => $e->getTenantId(), 'name' => $e->getName(), 'code' => $e->getCode(), 'type' => $e->getType(), 'calculation_type' => $e->getCalculationType(), 'value' => $e->getValue(), 'is_active' => $e->isActive(), 'is_taxable' => $e->isTaxable(), 'account_id' => $e->getAccountId(), 'metadata' => $e->getMetadata()];
        $m = $e->getId() ? $this->update($e->getId(), $data) : $this->create($data);

        return $this->toDomainEntity($m);
    }

    public function find(int|string $id, array $columns = ['*']): ?PayrollItem
    {
        return parent::find($id, $columns);
    }

    public function findByTenantAndCode(int $tenantId, string $code): ?PayrollItem
    {
        $m = $this->model->where('tenant_id', $tenantId)->where('code', $code)->first();

        return $m ? $this->toDomainEntity($m) : null;
    }

    private function map(PayrollItemModel $m): PayrollItem
    {
        $now = fn ($v) => $v instanceof \DateTimeInterface ? $v : new \DateTimeImmutable($v ?? 'now');

        return new PayrollItem($m->tenant_id, $m->name, $m->code, $m->type, $m->calculation_type, (string) $m->value, (bool) $m->is_active, (bool) $m->is_taxable, $m->account_id, $m->metadata ?? [], $now($m->created_at), $now($m->updated_at), $m->id);
    }
}
