<?php

declare(strict_types=1);

namespace Modules\Customer\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\Customer\Domain\Entities\CustomerContact;
use Modules\Customer\Domain\RepositoryInterfaces\CustomerContactRepositoryInterface;
use Modules\Customer\Infrastructure\Persistence\Eloquent\Models\CustomerContactModel;

class EloquentCustomerContactRepository extends EloquentRepository implements CustomerContactRepositoryInterface
{
    public function __construct(CustomerContactModel $model)
    {
        parent::__construct($model);
        $this->setDomainEntityMapper(fn (CustomerContactModel $model): CustomerContact => $this->mapModelToDomainEntity($model));
    }

    public function save(CustomerContact $contact): CustomerContact
    {
        if ($contact->isPrimary()) {
            $this->clearPrimaryByCustomer(
                tenantId: $contact->getTenantId(),
                customerId: $contact->getCustomerId(),
                excludeId: $contact->getId(),
            );
        }

        $data = [
            'tenant_id' => $contact->getTenantId(),
            'customer_id' => $contact->getCustomerId(),
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

        /** @var CustomerContactModel $model */

        return $this->toDomainEntity($model);
    }

    public function clearPrimaryByCustomer(int $tenantId, int $customerId, ?int $excludeId = null): void
    {
        $query = $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('customer_id', $customerId)
            ->where('is_primary', true);

        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }

        $query->update(['is_primary' => false]);
    }

    public function find(int|string $id, array $columns = ['*']): ?CustomerContact
    {
        return parent::find($id, $columns);
    }

    private function mapModelToDomainEntity(CustomerContactModel $model): CustomerContact
    {
        return new CustomerContact(
            id: (int) $model->id,
            tenantId: (int) $model->tenant_id,
            customerId: (int) $model->customer_id,
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
