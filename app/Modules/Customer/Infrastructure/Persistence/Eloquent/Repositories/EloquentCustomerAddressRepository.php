<?php

declare(strict_types=1);

namespace Modules\Customer\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\Customer\Domain\Entities\CustomerAddress;
use Modules\Customer\Domain\RepositoryInterfaces\CustomerAddressRepositoryInterface;
use Modules\Customer\Infrastructure\Persistence\Eloquent\Models\CustomerAddressModel;

class EloquentCustomerAddressRepository extends EloquentRepository implements CustomerAddressRepositoryInterface
{
    public function __construct(CustomerAddressModel $model)
    {
        parent::__construct($model);
        $this->setDomainEntityMapper(fn (CustomerAddressModel $model): CustomerAddress => $this->mapModelToDomainEntity($model));
    }

    public function save(CustomerAddress $address): CustomerAddress
    {
        $data = [
            'tenant_id' => $address->getTenantId(),
            'customer_id' => $address->getCustomerId(),
            'type' => $address->getType(),
            'label' => $address->getLabel(),
            'address_line1' => $address->getAddressLine1(),
            'address_line2' => $address->getAddressLine2(),
            'city' => $address->getCity(),
            'state' => $address->getState(),
            'postal_code' => $address->getPostalCode(),
            'country_id' => $address->getCountryId(),
            'is_default' => $address->isDefault(),
            'geo_lat' => $address->getGeoLat(),
            'geo_lng' => $address->getGeoLng(),
        ];

        if ($address->getId()) {
            $model = $this->update($address->getId(), $data);
        } else {
            $model = $this->create($data);
        }

        /** @var CustomerAddressModel $model */

        return $this->toDomainEntity($model);
    }

    public function clearDefaultByCustomerAndType(int $tenantId, int $customerId, string $type, ?int $excludeId = null): void
    {
        $query = $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('customer_id', $customerId)
            ->where('type', $type)
            ->where('is_default', true);

        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }

        $query->update(['is_default' => false]);
    }

    public function find(int|string $id, array $columns = ['*']): ?CustomerAddress
    {
        return parent::find($id, $columns);
    }

    private function mapModelToDomainEntity(CustomerAddressModel $model): CustomerAddress
    {
        return new CustomerAddress(
            id: (int) $model->id,
            tenantId: (int) $model->tenant_id,
            customerId: (int) $model->customer_id,
            type: (string) $model->type,
            label: $model->label,
            addressLine1: (string) $model->address_line1,
            addressLine2: $model->address_line2,
            city: (string) $model->city,
            state: $model->state,
            postalCode: (string) $model->postal_code,
            countryId: (int) $model->country_id,
            isDefault: (bool) $model->is_default,
            geoLat: $model->geo_lat !== null ? (string) $model->geo_lat : null,
            geoLng: $model->geo_lng !== null ? (string) $model->geo_lng : null,
            createdAt: $model->created_at,
            updatedAt: $model->updated_at,
        );
    }
}
