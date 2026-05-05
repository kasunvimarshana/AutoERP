<?php

declare(strict_types=1);

namespace Modules\Supplier\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Supplier\Application\Contracts\UpdateSupplierAddressServiceInterface;
use Modules\Supplier\Application\DTOs\SupplierAddressData;
use Modules\Supplier\Domain\Entities\SupplierAddress;
use Modules\Supplier\Domain\Exceptions\SupplierAddressNotFoundException;
use Modules\Supplier\Domain\Exceptions\SupplierNotFoundException;
use Modules\Supplier\Domain\RepositoryInterfaces\SupplierAddressRepositoryInterface;
use Modules\Supplier\Domain\RepositoryInterfaces\SupplierRepositoryInterface;

class UpdateSupplierAddressService extends BaseService implements UpdateSupplierAddressServiceInterface
{
    public function __construct(
        private readonly SupplierAddressRepositoryInterface $supplierAddressRepository,
        private readonly SupplierRepositoryInterface $supplierRepository,
    ) {
        parent::__construct($supplierAddressRepository);
    }

    protected function handle(array $data): SupplierAddress
    {
        $id = (int) ($data['id'] ?? 0);
        $address = $this->supplierAddressRepository->find($id);
        if (! $address) {
            throw new SupplierAddressNotFoundException($id);
        }

        $dto = SupplierAddressData::fromArray($data);

        if ($address->getSupplierId() !== $dto->supplierId) {
            throw new SupplierAddressNotFoundException($id);
        }

        $supplier = $this->supplierRepository->find($dto->supplierId);
        if (! $supplier || $supplier->getTenantId() !== $address->getTenantId()) {
            throw new SupplierNotFoundException($dto->supplierId);
        }

        $address->update(
            type: $dto->type,
            label: $dto->label,
            addressLine1: $dto->addressLine1,
            addressLine2: $dto->addressLine2,
            city: $dto->city,
            state: $dto->state,
            postalCode: $dto->postalCode,
            countryId: $dto->countryId,
            isDefault: $dto->isDefault,
            geoLat: $dto->geoLat,
            geoLng: $dto->geoLng,
        );

        if ($dto->isDefault) {
            $this->supplierAddressRepository->clearDefaultBySupplierAndType(
                tenantId: $supplier->getTenantId(),
                supplierId: $dto->supplierId,
                type: $dto->type,
                excludeId: $id,
            );
        }

        return $this->supplierAddressRepository->save($address);
    }
}
