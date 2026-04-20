<?php

declare(strict_types=1);

namespace Modules\Supplier\Application\Services;

use Modules\Core\Application\Services\BaseService;
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

        if ($supplier->getTenantId() !== $dto->tenant_id) {
            throw new SupplierNotFoundException($id);
        }

        if ($dto->user_id !== null && $dto->user_id !== $supplier->getUserId()) {
            throw new DomainException('Changing supplier user association is not allowed.');
        }

        $supplier->update(
            userId: $supplier->getUserId(),
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
