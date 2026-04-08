<?php

declare(strict_types=1);

namespace Modules\Supplier\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Supplier\Application\Contracts\UpdateSupplierServiceInterface;
use Modules\Supplier\Application\DTOs\SupplierData;
use Modules\Supplier\Domain\Entities\Supplier;
use Modules\Supplier\Domain\Events\SupplierUpdated;
use Modules\Supplier\Domain\Exceptions\SupplierNotFoundException;
use Modules\Supplier\Domain\RepositoryInterfaces\SupplierRepositoryInterface;

class UpdateSupplierService extends BaseService implements UpdateSupplierServiceInterface
{
    public function __construct(private readonly SupplierRepositoryInterface $supplierRepository)
    {
        parent::__construct($supplierRepository);
    }

    protected function handle(array $data): Supplier
    {
        $id = $data['id'];
        $supplier = $this->supplierRepository->find($id);

        if (! $supplier) {
            throw new SupplierNotFoundException($id);
        }

        $dto = SupplierData::fromArray($data);

        $supplier->updateDetails(
            name: $dto->name,
            code: $dto->code,
            userId: $dto->user_id,
            email: $dto->email,
            phone: $dto->phone,
            address: $dto->address,
            contactPerson: $dto->contact_person,
            paymentTerms: $dto->payment_terms,
            currency: $dto->currency ?? $supplier->getCurrency(),
            taxNumber: $dto->tax_number,
            type: $dto->type ?? $supplier->getType(),
            attributes: $dto->attributes,
            metadata: $dto->metadata,
        );

        if (isset($dto->status)) {
            if ($dto->status === 'active') {
                $supplier->activate();
            } elseif ($dto->status === 'inactive') {
                $supplier->deactivate();
            }
        }

        $saved = $this->supplierRepository->save($supplier);

        $this->addEvent(new SupplierUpdated($saved));

        return $saved;
    }
}
