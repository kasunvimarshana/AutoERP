<?php

declare(strict_types=1);

namespace Modules\Supplier\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Supplier\Application\Contracts\CreateSupplierContactServiceInterface;
use Modules\Supplier\Application\DTOs\SupplierContactData;
use Modules\Supplier\Domain\Entities\SupplierContact;
use Modules\Supplier\Domain\Exceptions\SupplierNotFoundException;
use Modules\Supplier\Domain\RepositoryInterfaces\SupplierContactRepositoryInterface;
use Modules\Supplier\Domain\RepositoryInterfaces\SupplierRepositoryInterface;

class CreateSupplierContactService extends BaseService implements CreateSupplierContactServiceInterface
{
    public function __construct(
        private readonly SupplierContactRepositoryInterface $supplierContactRepository,
        private readonly SupplierRepositoryInterface $supplierRepository,
    ) {
        parent::__construct($supplierContactRepository);
    }

    protected function handle(array $data): SupplierContact
    {
        $dto = SupplierContactData::fromArray($data);

        $supplier = $this->supplierRepository->find($dto->supplierId);
        if (! $supplier) {
            throw new SupplierNotFoundException($dto->supplierId);
        }

        $contact = new SupplierContact(
            tenantId: $supplier->getTenantId(),
            supplierId: $dto->supplierId,
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
            );
        }

        return $this->supplierContactRepository->save($contact);
    }
}
