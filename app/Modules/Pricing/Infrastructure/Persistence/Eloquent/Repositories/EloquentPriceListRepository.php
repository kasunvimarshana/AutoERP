<?php

declare(strict_types=1);

namespace Modules\Pricing\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\Pricing\Domain\Entities\PriceList;
use Modules\Pricing\Domain\RepositoryInterfaces\PriceListRepositoryInterface;
use Modules\Pricing\Infrastructure\Persistence\Eloquent\Models\PriceListModel;

class EloquentPriceListRepository extends EloquentRepository implements PriceListRepositoryInterface
{
    public function __construct(PriceListModel $model)
    {
        parent::__construct($model);
        $this->setDomainEntityMapper(fn (PriceListModel $model): PriceList => $this->mapModelToDomainEntity($model));
    }

    public function save(PriceList $priceList): PriceList
    {
        $data = [
            'tenant_id' => $priceList->getTenantId(),
            'name' => $priceList->getName(),
            'type' => $priceList->getType(),
            'currency_id' => $priceList->getCurrencyId(),
            'is_default' => $priceList->isDefault(),
            'valid_from' => $priceList->getValidFrom()?->format('Y-m-d'),
            'valid_to' => $priceList->getValidTo()?->format('Y-m-d'),
            'is_active' => $priceList->isActive(),
        ];

        if ($priceList->getId()) {
            $model = $this->update($priceList->getId(), $data);
        } else {
            $model = $this->create($data);
        }

        /** @var PriceListModel $model */

        return $this->toDomainEntity($model);
    }

    public function findByTenantAndName(int $tenantId, string $name): ?PriceList
    {
        /** @var PriceListModel|null $model */
        $model = $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('name', $name)
            ->first();

        return $model ? $this->toDomainEntity($model) : null;
    }

    public function clearDefaultByType(int $tenantId, string $type, ?int $excludeId = null): void
    {
        $query = $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('type', $type)
            ->where('is_default', true);

        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }

        $query->update(['is_default' => false]);
    }

    public function find(int|string $id, array $columns = ['*']): ?PriceList
    {
        return parent::find($id, $columns);
    }

    private function mapModelToDomainEntity(PriceListModel $model): PriceList
    {
        return new PriceList(
            id: (int) $model->id,
            tenantId: (int) $model->tenant_id,
            name: (string) $model->name,
            type: (string) $model->type,
            currencyId: (int) $model->currency_id,
            isDefault: (bool) $model->is_default,
            validFrom: $model->valid_from,
            validTo: $model->valid_to,
            isActive: (bool) $model->is_active,
            createdAt: $model->created_at,
            updatedAt: $model->updated_at,
        );
    }
}
