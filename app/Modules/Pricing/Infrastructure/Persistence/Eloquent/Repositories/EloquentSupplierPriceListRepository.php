<?php

declare(strict_types=1);

namespace Modules\Pricing\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\Pricing\Domain\Entities\SupplierPriceList;
use Modules\Pricing\Domain\RepositoryInterfaces\SupplierPriceListRepositoryInterface;
use Modules\Pricing\Infrastructure\Persistence\Eloquent\Models\SupplierPriceListModel;

class EloquentSupplierPriceListRepository extends EloquentRepository implements SupplierPriceListRepositoryInterface
{
    public function __construct(SupplierPriceListModel $model)
    {
        parent::__construct($model);
        $this->setDomainEntityMapper(fn (SupplierPriceListModel $model): SupplierPriceList => $this->mapModelToDomainEntity($model));
    }

    public function save(SupplierPriceList $supplierPriceList): SupplierPriceList
    {
        $data = [
            'tenant_id' => $supplierPriceList->getTenantId(),
            'supplier_id' => $supplierPriceList->getSupplierId(),
            'price_list_id' => $supplierPriceList->getPriceListId(),
            'priority' => $supplierPriceList->getPriority(),
        ];

        if ($supplierPriceList->getId()) {
            $model = $this->update($supplierPriceList->getId(), $data);
        } else {
            $model = $this->create($data);
        }

        /** @var SupplierPriceListModel $model */

        return $this->toDomainEntity($model);
    }

    public function find(int|string $id, array $columns = ['*']): ?SupplierPriceList
    {
        return parent::find($id, $columns);
    }

    private function mapModelToDomainEntity(SupplierPriceListModel $model): SupplierPriceList
    {
        return new SupplierPriceList(
            id: (int) $model->id,
            tenantId: (int) $model->tenant_id,
            supplierId: (int) $model->supplier_id,
            priceListId: (int) $model->price_list_id,
            priority: (int) ($model->priority ?? 0),
            createdAt: $model->created_at,
            updatedAt: $model->updated_at,
        );
    }
}
