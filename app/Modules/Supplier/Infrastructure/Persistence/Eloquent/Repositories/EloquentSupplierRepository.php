<?php

declare(strict_types=1);

namespace Modules\Supplier\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Support\Collection;
use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\Supplier\Domain\Entities\Supplier;
use Modules\Supplier\Domain\RepositoryInterfaces\SupplierRepositoryInterface;
use Modules\Supplier\Infrastructure\Persistence\Eloquent\Models\SupplierModel;

class EloquentSupplierRepository extends EloquentRepository implements SupplierRepositoryInterface
{
    public function __construct(SupplierModel $model)
    {
        parent::__construct($model);
        $this->setDomainEntityMapper(fn (SupplierModel $model): Supplier => $this->mapModelToDomainEntity($model));
    }

    public function findByCode(int $tenantId, string $code): ?Supplier
    {
        $model = $this->model->where('tenant_id', $tenantId)->where('code', $code)->first();

        return $model ? $this->toDomainEntity($model) : null;
    }

    public function findByTenant(int $tenantId): Collection
    {
        return $this->toDomainCollection($this->model->where('tenant_id', $tenantId)->get());
    }

    public function findByUserId(int $tenantId, int $userId): ?Supplier
    {
        $model = $this->model->where('tenant_id', $tenantId)->where('user_id', $userId)->first();

        return $model ? $this->toDomainEntity($model) : null;
    }

    public function save(Supplier $supplier): Supplier
    {
        $data = [
            'tenant_id'      => $supplier->getTenantId(),
            'user_id'        => $supplier->getUserId(),
            'name'           => $supplier->getName(),
            'code'           => $supplier->getCode(),
            'email'          => $supplier->getEmail(),
            'phone'          => $supplier->getPhone(),
            'address'        => $supplier->getAddress(),
            'contact_person' => $supplier->getContactPerson(),
            'payment_terms'  => $supplier->getPaymentTerms(),
            'currency'       => $supplier->getCurrency(),
            'tax_number'     => $supplier->getTaxNumber(),
            'status'         => $supplier->getStatus(),
            'type'           => $supplier->getType(),
            'attributes'     => $supplier->getAttributes(),
            'metadata'       => $supplier->getMetadata(),
        ];

        if ($supplier->getId()) {
            $model = $this->update($supplier->getId(), $data);
        } else {
            $model = $this->create($data);
        }

        return $this->toDomainEntity($model);
    }

    private function mapModelToDomainEntity(SupplierModel $model): Supplier
    {
        return new Supplier(
            tenantId: $model->tenant_id,
            name: $model->name,
            code: $model->code,
            userId: $model->user_id,
            email: $model->email,
            phone: $model->phone,
            address: $model->address,
            contactPerson: $model->contact_person,
            paymentTerms: $model->payment_terms,
            currency: $model->currency,
            taxNumber: $model->tax_number,
            status: $model->status,
            type: $model->type,
            attributes: $model->attributes,
            metadata: $model->metadata,
            id: $model->id,
            createdAt: $model->created_at,
            updatedAt: $model->updated_at,
        );
    }
}
