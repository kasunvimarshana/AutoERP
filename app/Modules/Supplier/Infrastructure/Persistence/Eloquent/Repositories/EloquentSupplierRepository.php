<?php

declare(strict_types=1);

namespace Modules\Supplier\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Supplier\Domain\Entities\Supplier;
use Modules\Supplier\Domain\RepositoryInterfaces\SupplierRepositoryInterface;
use Modules\Supplier\Infrastructure\Persistence\Eloquent\Models\SupplierModel;

class EloquentSupplierRepository implements SupplierRepositoryInterface
{
    public function findById(string $tenantId, string $id): ?Supplier
    {
        $model = SupplierModel::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->find($id);
        return $model !== null ? $this->mapToEntity($model) : null;
    }

    public function findAll(string $tenantId): array
    {
        return SupplierModel::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->get()
            ->map(fn(SupplierModel $m) => $this->mapToEntity($m))
            ->all();
    }

    public function findByCode(string $tenantId, string $code): ?Supplier
    {
        $model = SupplierModel::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('code', $code)
            ->first();
        return $model !== null ? $this->mapToEntity($model) : null;
    }

    public function findActive(string $tenantId): array
    {
        return SupplierModel::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->get()
            ->map(fn(SupplierModel $m) => $this->mapToEntity($m))
            ->all();
    }

    public function save(Supplier $supplier): void
    {
        $model = SupplierModel::withoutGlobalScopes()->findOrNew($supplier->id);
        $model->fill([
            'tenant_id'    => $supplier->tenantId,
            'name'         => $supplier->name,
            'code'         => $supplier->code,
            'email'        => $supplier->email,
            'phone'        => $supplier->phone,
            'address'      => $supplier->address,
            'tax_number'   => $supplier->taxNumber,
            'currency'     => $supplier->currency,
            'credit_limit' => $supplier->creditLimit,
            'is_active'    => $supplier->isActive,
        ]);
        if (!$model->exists) {
            $model->id = $supplier->id;
        }
        $model->save();
    }

    public function delete(string $tenantId, string $id): void
    {
        SupplierModel::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->find($id)?->delete();
    }

    private function mapToEntity(SupplierModel $model): Supplier
    {
        return new Supplier(
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
            isActive: (bool) $model->is_active,
            createdAt: $model->created_at,
            updatedAt: $model->updated_at,
        );
    }
}
