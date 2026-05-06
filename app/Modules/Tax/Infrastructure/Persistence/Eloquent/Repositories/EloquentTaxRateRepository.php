<?php

declare(strict_types=1);

namespace Modules\Tax\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Support\Collection;
use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\Tax\Domain\Entities\TaxRate;
use Modules\Tax\Domain\RepositoryInterfaces\TaxRateRepositoryInterface;
use Modules\Tax\Infrastructure\Persistence\Eloquent\Models\TaxRateModel;

class EloquentTaxRateRepository extends EloquentRepository implements TaxRateRepositoryInterface
{
    public function __construct(TaxRateModel $model)
    {
        parent::__construct($model);
        $this->setDomainEntityMapper(fn (TaxRateModel $model): TaxRate => $this->mapModelToDomainEntity($model));
    }

    public function save(TaxRate $taxRate): TaxRate
    {
        $data = [
            'tenant_id' => $taxRate->getTenantId(),
            'tax_group_id' => $taxRate->getTaxGroupId(),
            'name' => $taxRate->getName(),
            'rate' => $taxRate->getRate(),
            'type' => $taxRate->getType(),
            'account_id' => $taxRate->getAccountId(),
            'is_compound' => $taxRate->isCompound(),
            'is_active' => $taxRate->isActive(),
            'valid_from' => $taxRate->getValidFrom()?->format('Y-m-d'),
            'valid_to' => $taxRate->getValidTo()?->format('Y-m-d'),
            'row_version' => $taxRate->getRowVersion(),
        ];

        if ($taxRate->getId()) {
            $model = $this->update($taxRate->getId(), $data);
        } else {
            $model = $this->create($data);
        }

        /** @var TaxRateModel $model */

        return $this->toDomainEntity($model);
    }

    public function findByTenantGroupAndName(int $tenantId, int $taxGroupId, string $name): ?TaxRate
    {
        /** @var TaxRateModel|null $model */
        $model = $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('tax_group_id', $taxGroupId)
            ->where('name', $name)
            ->first();

        return $model ? $this->toDomainEntity($model) : null;
    }

    public function findActiveByGroup(int $tenantId, int $taxGroupId, \DateTimeInterface $onDate): array
    {
        $date = $onDate->format('Y-m-d');

        /** @var Collection<int, TaxRateModel> $models */
        $models = $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('tax_group_id', $taxGroupId)
            ->where('is_active', true)
            ->where(function ($query) use ($date): void {
                $query->whereNull('valid_from')->orWhere('valid_from', '<=', $date);
            })
            ->where(function ($query) use ($date): void {
                $query->whereNull('valid_to')->orWhere('valid_to', '>=', $date);
            })
            ->orderBy('is_compound')
            ->orderBy('id')
            ->get();

        return $models->map(fn (TaxRateModel $model): TaxRate => $this->toDomainEntity($model))->all();
    }

    public function find(int|string $id, array $columns = ['*']): ?TaxRate
    {
        return parent::find($id, $columns);
    }

    private function mapModelToDomainEntity(TaxRateModel $model): TaxRate
    {
        return new TaxRate(
            id: (int) $model->id,
            tenantId: (int) $model->tenant_id,
            taxGroupId: (int) $model->tax_group_id,
            name: (string) $model->name,
            rate: (string) $model->rate,
            type: (string) $model->type,
            accountId: $model->account_id !== null ? (int) $model->account_id : null,
            isCompound: (bool) $model->is_compound,
            isActive: (bool) $model->is_active,
            validFrom: $model->valid_from,
            validTo: $model->valid_to,
            rowVersion: (int) ($model->row_version ?? 1),
            createdAt: $model->created_at,
            updatedAt: $model->updated_at,
        );
    }
}
