<?php

declare(strict_types=1);

namespace Modules\Customer\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Customer\Domain\Entities\Customer;
use Modules\Customer\Domain\RepositoryInterfaces\CustomerRepositoryInterface;
use Modules\Customer\Infrastructure\Persistence\Eloquent\Models\CustomerModel;

class EloquentCustomerRepository implements CustomerRepositoryInterface
{
    public function findById(string $tenantId, string $id): ?Customer
    {
        $model = CustomerModel::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->find($id);
        return $model !== null ? $this->mapToEntity($model) : null;
    }

    public function findAll(string $tenantId): array
    {
        return CustomerModel::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->get()
            ->map(fn(CustomerModel $m) => $this->mapToEntity($m))
            ->all();
    }

    public function findByCode(string $tenantId, string $code): ?Customer
    {
        $model = CustomerModel::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('code', $code)
            ->first();
        return $model !== null ? $this->mapToEntity($model) : null;
    }

    public function findActive(string $tenantId): array
    {
        return CustomerModel::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->get()
            ->map(fn(CustomerModel $m) => $this->mapToEntity($m))
            ->all();
    }

    public function save(Customer $customer): void
    {
        $model = CustomerModel::withoutGlobalScopes()->findOrNew($customer->id);
        $model->fill([
            'tenant_id'    => $customer->tenantId,
            'name'         => $customer->name,
            'code'         => $customer->code,
            'email'        => $customer->email,
            'phone'        => $customer->phone,
            'address'      => $customer->address,
            'tax_number'   => $customer->taxNumber,
            'currency'     => $customer->currency,
            'credit_limit' => $customer->creditLimit,
            'balance'      => $customer->balance,
            'is_active'    => $customer->isActive,
        ]);
        if (!$model->exists) {
            $model->id = $customer->id;
        }
        $model->save();
    }

    public function delete(string $tenantId, string $id): void
    {
        CustomerModel::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->find($id)?->delete();
    }

    private function mapToEntity(CustomerModel $model): Customer
    {
        return new Customer(
            id: $model->id,
            tenantId: $model->tenant_id,
            name: $model->name,
            code: $model->code,
            email: $model->email,
            phone: $model->phone,
            address: $model->address,
            taxNumber: $model->tax_number,
            currency: $model->currency,
            creditLimit: (float) $model->credit_limit,
            balance: (float) $model->balance,
            isActive: (bool) $model->is_active,
            createdAt: $model->created_at,
            updatedAt: $model->updated_at,
        );
    }
}
