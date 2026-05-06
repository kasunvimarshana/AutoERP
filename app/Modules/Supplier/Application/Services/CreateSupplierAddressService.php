<?php

declare(strict_types=1);

namespace Modules\Supplier\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Supplier\Application\Contracts\CreateSupplierAddressServiceInterface;
use Modules\Supplier\Application\DTOs\SupplierAddressData;
use Modules\Supplier\Domain\Entities\SupplierAddress;
use Modules\Supplier\Domain\Exceptions\SupplierNotFoundException;
use Modules\Supplier\Domain\RepositoryInterfaces\SupplierAddressRepositoryInterface;
use Modules\Supplier\Domain\RepositoryInterfaces\SupplierRepositoryInterface;

class CreateSupplierAddressService extends BaseService implements CreateSupplierAddressServiceInterface
{
    public function __construct(
        private readonly SupplierAddressRepositoryInterface $supplierAddressRepository,
        private readonly SupplierRepositoryInterface $supplierRepository,
    ) {
        parent::__construct($supplierAddressRepository);
    }

    protected function handle(array $data): SupplierAddress
    {
        $dto = SupplierAddressData::fromArray($data);

        $supplier = $this->supplierRepository->find($dto->supplier_id);
        if (! $supplier) {
            throw new SupplierNotFoundException($dto->supplier_id);
        }

        $address = new SupplierAddress(
            tenantId: $supplier->getTenantId(),
            supplierId: $dto->supplier_id,
            type: $dto->type,
            label: $dto->label,
            addressLine1: $dto->address_line1,
            addressLine2: $dto->address_line2,
            city: $dto->city,
            state: $dto->state,
            postalCode: $dto->postal_code,
            countryId: $dto->country_id,
            isDefault: $dto->is_default,
            geoLat: $dto->geo_lat,
            geoLng: $dto->geo_lng,
        );

        if ($dto->is_default) {
            $this->supplierAddressRepository->clearDefaultBySupplierAndType(
                tenantId: $supplier->getTenantId(),
                supplierId: $dto->supplier_id,
                type: $dto->type,
            );
        }

        return $this->supplierAddressRepository->save($address);
    }
}
