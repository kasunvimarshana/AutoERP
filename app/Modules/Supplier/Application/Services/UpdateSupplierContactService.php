<?php

declare(strict_types=1);

namespace Modules\Supplier\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Supplier\Application\Contracts\UpdateSupplierContactServiceInterface;
use Modules\Supplier\Application\DTOs\SupplierContactData;
use Modules\Supplier\Domain\Entities\SupplierContact;
use Modules\Supplier\Domain\Exceptions\SupplierContactNotFoundException;
use Modules\Supplier\Domain\Exceptions\SupplierNotFoundException;
use Modules\Supplier\Domain\RepositoryInterfaces\SupplierContactRepositoryInterface;
use Modules\Supplier\Domain\RepositoryInterfaces\SupplierRepositoryInterface;

class UpdateSupplierContactService extends BaseService implements UpdateSupplierContactServiceInterface
{
    public function __construct(
        private readonly SupplierContactRepositoryInterface $supplierContactRepository,
        private readonly SupplierRepositoryInterface $supplierRepository,
    ) {
        parent::__construct($supplierContactRepository);
    }

    protected function handle(array $data): SupplierContact
    {
        $id = (int) ($data['id'] ?? 0);
        $contact = $this->supplierContactRepository->find($id);
        if (! $contact) {
            throw new SupplierContactNotFoundException($id);
        }

        $dto = SupplierContactData::fromArray($data);
        if ($contact->getSupplierId() !== $dto->supplierId) {
            throw new SupplierContactNotFoundException($id);
        }

        $supplier = $this->supplierRepository->find($dto->supplierId);
        if (! $supplier || $supplier->getTenantId() !== $contact->getTenantId()) {
            throw new SupplierNotFoundException($dto->supplierId);
        }

        $contact->update(
            name: $dto->name,
            role: $dto->role,
            email: $dto->email,
            phone: $dto->phone,
            isPrimary: $dto->isPrimary,
        );

        if ($dto->isPrimary) {
            $this->supplierContactRepository->clearPrimaryBySupplier(
                tenantId: $supplier->getTenantId(),
                supplierId: $dto->supplierId,
                excludeId: $id,
            );
        }

        return $this->supplierContactRepository->save($contact);
    }
}
