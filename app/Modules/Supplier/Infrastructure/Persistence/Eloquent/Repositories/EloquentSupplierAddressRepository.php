<?php

declare(strict_types=1);

namespace Modules\Supplier\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\Supplier\Domain\Entities\SupplierAddress;
use Modules\Supplier\Domain\RepositoryInterfaces\SupplierAddressRepositoryInterface;
use Modules\Supplier\Infrastructure\Persistence\Eloquent\Models\SupplierAddressModel;

class EloquentSupplierAddressRepository extends EloquentRepository implements SupplierAddressRepositoryInterface
{
    public function __construct(SupplierAddressModel $model)
    {
        parent::__construct($model);
        $this->setDomainEntityMapper(fn (SupplierAddressModel $model): SupplierAddress => $this->mapModelToDomainEntity($model));
    }

    public function save(SupplierAddress $address): SupplierAddress
    {
        $data = [
            'tenant_id' => $address->getTenantId(),
            'supplier_id' => $address->getSupplierId(),
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

        /** @var SupplierAddressModel $model */

        return $this->toDomainEntity($model);
    }

    public function clearDefaultBySupplierAndType(int $tenantId, int $supplierId, string $type, ?int $excludeId = null): void
    {
        $query = $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('supplier_id', $supplierId)
            ->where('type', $type)
            ->where('is_default', true);

        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }

        $query->update(['is_default' => false]);
    }

    public function find(int|string $id, array $columns = ['*']): ?SupplierAddress
    {
        return parent::find($id, $columns);
    }

    private function mapModelToDomainEntity(SupplierAddressModel $model): SupplierAddress
    {
        return new SupplierAddress(
            id: (int) $model->id,
            tenantId: (int) $model->tenant_id,
            supplierId: (int) $model->supplier_id,
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
