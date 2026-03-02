<?php

declare(strict_types=1);

namespace Modules\Procurement\Infrastructure\Repositories;

use App\Shared\Abstractions\BaseRepository;
use Modules\Procurement\Domain\Contracts\SupplierRepositoryInterface;
use Modules\Procurement\Domain\Entities\Supplier;
use Modules\Procurement\Infrastructure\Models\SupplierModel;

class SupplierRepository extends BaseRepository implements SupplierRepositoryInterface
{
    protected function model(): string
    {
        return SupplierModel::class;
    }

    public function findById(int $id, int $tenantId): ?Supplier
    {
        $model = $this->newQuery()
            ->where('id', $id)
            ->where('tenant_id', $tenantId)
            ->first();

        return $model ? $this->toDomain($model) : null;
    }

    public function findAll(int $tenantId, int $page = 1, int $perPage = 25): array
    {
        $paginator = $this->newQuery()
            ->where('tenant_id', $tenantId)
            ->orderBy('name')
            ->paginate($perPage, ['*'], 'page', $page);

        return [
            'items' => $paginator->getCollection()->map(fn (SupplierModel $m) => $this->toDomain($m))->all(),
            'total' => $paginator->total(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
        ];
    }

    public function save(Supplier $supplier): Supplier
    {
        if ($supplier->id !== null) {
            $model = $this->newQuery()
                ->where('id', $supplier->id)
                ->where('tenant_id', $supplier->tenantId)
                ->firstOrFail();
        } else {
            $model = new SupplierModel;
            $model->tenant_id = $supplier->tenantId;
        }

        $model->name = $supplier->name;
        $model->contact_name = $supplier->contactName;
        $model->email = $supplier->email;
        $model->phone = $supplier->phone;
        $model->address = $supplier->address;
        $model->status = $supplier->status;
        $model->notes = $supplier->notes;
        $model->save();

        return $this->toDomain($model);
    }

    public function delete(int $id, int $tenantId): void
    {
        $model = $this->newQuery()
            ->where('id', $id)
            ->where('tenant_id', $tenantId)
            ->first();

        if ($model === null) {
            throw new \DomainException("Supplier with ID {$id} not found.");
        }

        $model->delete();
    }

    private function toDomain(SupplierModel $model): Supplier
    {
        return new Supplier(
            id: $model->id,
            tenantId: $model->tenant_id,
            name: $model->name,
            contactName: $model->contact_name,
            email: $model->email,
            phone: $model->phone,
            address: $model->address,
            status: $model->status,
            notes: $model->notes,
            createdAt: $model->created_at?->toIso8601String(),
            updatedAt: $model->updated_at?->toIso8601String(),
        );
    }
}
