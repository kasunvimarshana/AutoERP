<?php

declare(strict_types=1);

namespace Modules\Pricing\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\Pricing\Domain\Entities\CustomerPriceList;
use Modules\Pricing\Domain\RepositoryInterfaces\CustomerPriceListRepositoryInterface;
use Modules\Pricing\Infrastructure\Persistence\Eloquent\Models\CustomerPriceListModel;

class EloquentCustomerPriceListRepository extends EloquentRepository implements CustomerPriceListRepositoryInterface
{
    public function __construct(CustomerPriceListModel $model)
    {
        parent::__construct($model);
        $this->setDomainEntityMapper(fn (CustomerPriceListModel $model): CustomerPriceList => $this->mapModelToDomainEntity($model));
    }

    public function save(CustomerPriceList $customerPriceList): CustomerPriceList
    {
        $data = [
            'tenant_id' => $customerPriceList->getTenantId(),
            'customer_id' => $customerPriceList->getCustomerId(),
            'price_list_id' => $customerPriceList->getPriceListId(),
            'priority' => $customerPriceList->getPriority(),
        ];

        if ($customerPriceList->getId()) {
            $model = $this->update($customerPriceList->getId(), $data);
        } else {
            $model = $this->create($data);
        }

        /** @var CustomerPriceListModel $model */

        return $this->toDomainEntity($model);
    }

    public function find(int|string $id, array $columns = ['*']): ?CustomerPriceList
    {
        return parent::find($id, $columns);
    }

    private function mapModelToDomainEntity(CustomerPriceListModel $model): CustomerPriceList
    {
        return new CustomerPriceList(
            id: (int) $model->id,
            tenantId: (int) $model->tenant_id,
            customerId: (int) $model->customer_id,
            priceListId: (int) $model->price_list_id,
            priority: (int) $model->priority,
            createdAt: $model->created_at,
            updatedAt: $model->updated_at,
        );
    }
}
