<?php

declare(strict_types=1);

namespace Modules\Supplier\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Core\Domain\Exceptions\DomainException;
use Modules\Supplier\Application\Contracts\CreateSupplierServiceInterface;
use Modules\Supplier\Application\DTOs\SupplierData;
use Modules\Supplier\Domain\Contracts\SupplierUserSynchronizerInterface;
use Modules\Supplier\Domain\Entities\Supplier;
use Modules\Supplier\Domain\RepositoryInterfaces\SupplierRepositoryInterface;

class CreateSupplierService extends BaseService implements CreateSupplierServiceInterface
{
    public function __construct(
        private readonly SupplierRepositoryInterface $supplierRepository,
        private readonly SupplierUserSynchronizerInterface $supplierUserSynchronizer,
    ) {
        parent::__construct($supplierRepository);
    }

    protected function handle(array $data): Supplier
    {
        $dto = SupplierData::fromArray($data);

        $resolvedUserId = $this->supplierUserSynchronizer->resolveUserIdForCreate(
            tenantId: $dto->tenantId,
            orgUnitId: $dto->orgUnitId,
            requestedUserId: $dto->userId,
            userPayload: $dto->user,
        );

        $existingSupplier = $this->supplierRepository->findByTenantAndUserId($dto->tenantId, $resolvedUserId);
        if ($existingSupplier !== null) {
            throw new DomainException('The user is already linked to another supplier.');
        }

        $supplier = new Supplier(
            tenantId: $dto->tenantId,
            userId: $resolvedUserId,
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

        return $this->supplierRepository->save($supplier);
    }
}
