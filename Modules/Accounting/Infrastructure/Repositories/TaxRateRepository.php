<?php

declare(strict_types=1);

namespace Modules\Accounting\Infrastructure\Repositories;

use Modules\Accounting\Domain\Contracts\TaxRateRepositoryInterface;
use Modules\Accounting\Domain\Entities\TaxRate as TaxRateEntity;
use Modules\Accounting\Infrastructure\Models\TaxRate as TaxRateModel;

class TaxRateRepository implements TaxRateRepositoryInterface
{
    public function findAll(int $tenantId): array
    {
        return TaxRateModel::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->orderBy('name')
            ->get()
            ->map(fn (TaxRateModel $m): TaxRateEntity => $this->toDomain($m))
            ->all();
    }

    public function findById(int $id, int $tenantId): ?TaxRateEntity
    {
        $m = TaxRateModel::withoutGlobalScope('tenant')
            ->where('id', $id)
            ->where('tenant_id', $tenantId)
            ->first();

        return $m ? $this->toDomain($m) : null;
    }

    public function save(TaxRateEntity $taxRate): TaxRateEntity
    {
        $data = [
            'tenant_id'   => $taxRate->getTenantId(),
            'name'        => $taxRate->getName(),
            'rate'        => $taxRate->getRate(),
            'type'        => $taxRate->getType(),
            'is_active'   => $taxRate->isActive(),
            'is_compound' => $taxRate->isCompound(),
        ];

        if ($taxRate->getId() > 0) {
            $m = TaxRateModel::withoutGlobalScope('tenant')->findOrFail($taxRate->getId());
            $m->update($data);
        } else {
            $m = TaxRateModel::create($data);
        }

        return $this->toDomain($m->fresh());
    }

    public function delete(int $id, int $tenantId): void
    {
        TaxRateModel::withoutGlobalScope('tenant')
            ->where('id', $id)
            ->where('tenant_id', $tenantId)
            ->first()
            ?->delete();
    }

    private function toDomain(TaxRateModel $m): TaxRateEntity
    {
        return new TaxRateEntity(
            id: (int) $m->id,
            tenantId: (int) $m->tenant_id,
            name: (string) $m->name,
            rate: bcadd((string) $m->rate, '0', 4),
            type: (string) $m->type,
            isActive: (bool) $m->is_active,
            isCompound: (bool) $m->is_compound,
        );
    }
}
