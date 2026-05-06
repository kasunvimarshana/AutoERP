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
            tenantId: $dto->tenant_id,
            orgUnitId: $dto->org_unit_id,
            requestedUserId: $dto->user_id,
            userPayload: $dto->user,
        );

        $existingSupplier = $this->supplierRepository->findByTenantAndUserId($dto->tenant_id, $resolvedUserId);
        if ($existingSupplier !== null) {
            throw new DomainException('The user is already linked to another supplier.');
        }

        $supplier = new Supplier(
            tenantId: $dto->tenant_id,
            userId: $resolvedUserId,
            supplierCode: $dto->supplier_code,
            name: $dto->name,
            type: $dto->type,
            orgUnitId: $dto->org_unit_id,
            taxNumber: $dto->tax_number,
            registrationNumber: $dto->registration_number,
            currencyId: $dto->currency_id,
            paymentTermsDays: $dto->payment_terms_days,
            apAccountId: $dto->ap_account_id,
            status: $dto->status,
            notes: $dto->notes,
            metadata: $dto->metadata,
        );

        return $this->supplierRepository->save($supplier);
    }
}
