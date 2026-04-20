<?php

declare(strict_types=1);

namespace Modules\Supplier\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\Supplier\Domain\Entities\SupplierContact;
use Modules\Supplier\Domain\RepositoryInterfaces\SupplierContactRepositoryInterface;
use Modules\Supplier\Infrastructure\Persistence\Eloquent\Models\SupplierContactModel;

class EloquentSupplierContactRepository extends EloquentRepository implements SupplierContactRepositoryInterface
{
    public function __construct(SupplierContactModel $model)
    {
        parent::__construct($model);
        $this->setDomainEntityMapper(fn (SupplierContactModel $model): SupplierContact => $this->mapModelToDomainEntity($model));
    }

    public function save(SupplierContact $contact): SupplierContact
    {
        $data = [
            'tenant_id' => $contact->getTenantId(),
            'supplier_id' => $contact->getSupplierId(),
            'name' => $contact->getName(),
            'role' => $contact->getRole(),
            'email' => $contact->getEmail(),
            'phone' => $contact->getPhone(),
            'is_primary' => $contact->isPrimary(),
        ];

        if ($contact->getId()) {
            $model = $this->update($contact->getId(), $data);
        } else {
            $model = $this->create($data);
        }

        /** @var SupplierContactModel $model */

        return $this->toDomainEntity($model);
    }

    public function clearPrimaryBySupplier(int $tenantId, int $supplierId, ?int $excludeId = null): void
    {
        $query = $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('supplier_id', $supplierId)
            ->where('is_primary', true);

        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }

        $query->update(['is_primary' => false]);
    }

    public function find(int|string $id, array $columns = ['*']): ?SupplierContact
    {
        return parent::find($id, $columns);
    }

    private function mapModelToDomainEntity(SupplierContactModel $model): SupplierContact
    {
        return new SupplierContact(
            id: (int) $model->id,
            tenantId: (int) $model->tenant_id,
            supplierId: (int) $model->supplier_id,
            name: (string) $model->name,
            role: $model->role,
            email: $model->email,
            phone: $model->phone,
            isPrimary: (bool) $model->is_primary,
            createdAt: $model->created_at,
            updatedAt: $model->updated_at,
        );
    }
}
