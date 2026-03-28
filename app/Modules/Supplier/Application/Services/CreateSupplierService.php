<?php

declare(strict_types=1);

namespace Modules\Supplier\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Supplier\Application\Contracts\CreateSupplierServiceInterface;
use Modules\Supplier\Application\DTOs\SupplierData;
use Modules\Supplier\Domain\Entities\Supplier;
use Modules\Supplier\Domain\Events\SupplierCreated;
use Modules\Supplier\Domain\RepositoryInterfaces\SupplierRepositoryInterface;

class CreateSupplierService extends BaseService implements CreateSupplierServiceInterface
{
    public function __construct(private readonly SupplierRepositoryInterface $supplierRepository)
    {
        parent::__construct($supplierRepository);
    }

    protected function handle(array $data): Supplier
    {
        $dto = SupplierData::fromArray($data);

        $supplier = new Supplier(
            tenantId: $dto->tenant_id,
            name: $dto->name,
            code: $dto->code,
            userId: $dto->user_id,
            email: $dto->email,
            phone: $dto->phone,
            address: $dto->address,
            contactPerson: $dto->contact_person,
            paymentTerms: $dto->payment_terms,
            currency: $dto->currency ?? 'USD',
            taxNumber: $dto->tax_number,
            status: $dto->status ?? 'active',
            type: $dto->type ?? 'other',
            attributes: $dto->attributes,
            metadata: $dto->metadata,
        );

        $saved = $this->supplierRepository->save($supplier);

        $this->addEvent(new SupplierCreated($saved));

        return $saved;
    }
}
