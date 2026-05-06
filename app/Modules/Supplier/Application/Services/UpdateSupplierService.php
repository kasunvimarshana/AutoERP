<?php

declare(strict_types=1);

namespace Modules\Supplier\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Core\Domain\Exceptions\ConcurrentModificationException;
use Modules\Core\Domain\Exceptions\DomainException;
use Modules\Supplier\Application\Contracts\UpdateSupplierServiceInterface;
use Modules\Supplier\Application\DTOs\SupplierData;
use Modules\Supplier\Domain\Contracts\SupplierUserSynchronizerInterface;
use Modules\Supplier\Domain\Entities\Supplier;
use Modules\Supplier\Domain\Exceptions\SupplierNotFoundException;
use Modules\Supplier\Domain\RepositoryInterfaces\SupplierRepositoryInterface;

class UpdateSupplierService extends BaseService implements UpdateSupplierServiceInterface
{
    public function __construct(
        private readonly SupplierRepositoryInterface $supplierRepository,
        private readonly SupplierUserSynchronizerInterface $supplierUserSynchronizer,
    ) {
        parent::__construct($supplierRepository);
    }

    protected function handle(array $data): Supplier
    {
        $id = (int) ($data['id'] ?? 0);
        $supplier = $this->supplierRepository->find($id);

        if (! $supplier) {
            throw new SupplierNotFoundException($id);
        }

        $dto = SupplierData::fromArray($data);

        if ($supplier->getTenantId() !== $dto->tenantId) {
            throw new SupplierNotFoundException($id);
        }

        if ($dto->rowVersion !== $supplier->getRowVersion()) {
            throw new ConcurrentModificationException('Supplier', $id);
        }

        if ($dto->userId !== null && $dto->userId !== $supplier->getUserId()) {
            throw new DomainException('Changing supplier user association is not allowed.');
        }

        $supplier->update(
            userId: $supplier->getUserId(),
            supplierCode: $dto->supplierCode,
            name: $dto->name,
            type: $dto->type,
            orgUnitId: $dto->orgUnitId,
            taxNumber: $dto->taxNumber,
            registrationNumber: $dto->registrationNumber,
            currencyId: $dto->currencyId,
            paymentTermsDays: $dto->paymentTermsDays,
            apAccountId: $dto->apAccountId,
            status: $dto->status,
            notes: $dto->notes,
            metadata: $dto->metadata,
        );

        $saved = $this->supplierRepository->save($supplier);

        $this->supplierUserSynchronizer->synchronizeForSupplierUpdate(
            tenantId: $saved->getTenantId(),
            userId: $saved->getUserId(),
            orgUnitId: $saved->getOrgUnitId(),
            userPayload: $dto->user,
        );

        return $saved;
    }
}
