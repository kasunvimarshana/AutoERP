<?php
namespace Modules\Supplier\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\Supplier\Domain\Entities\Supplier;
use Modules\Supplier\Domain\RepositoryInterfaces\SupplierRepositoryInterface;
use Modules\Supplier\Infrastructure\Persistence\Eloquent\Models\SupplierModel;

class EloquentSupplierRepository extends EloquentRepository implements SupplierRepositoryInterface
{
    public function __construct(SupplierModel $model)
    {
        parent::__construct($model);
    }

    public function findById(int $id): ?Supplier
    {
        $model = parent::findById($id);
        return $model ? $this->toEntity($model) : null;
    }

    public function findByCode(int $tenantId, string $code): ?Supplier
    {
        $model = $this->model->where('tenant_id', $tenantId)->where('code', $code)->first();
        return $model ? $this->toEntity($model) : null;
    }

    public function findAll(int $tenantId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->where('tenant_id', $tenantId);
        foreach ($filters as $key => $value) {
            $query->where($key, $value);
        }
        return $query->paginate($perPage);
    }

    public function create(array $data): Supplier
    {
        $model = parent::create($data);
        return $this->toEntity($model);
    }

    public function update(Supplier $supplier, array $data): Supplier
    {
        $model = $this->model->findOrFail($supplier->id);
        $updated = parent::update($model, $data);
        return $this->toEntity($updated);
    }

    public function delete(Supplier $supplier): bool
    {
        $model = $this->model->findOrFail($supplier->id);
        return parent::delete($model);
    }

    private function toEntity(object $model): Supplier
    {
        return new Supplier(
            id: $model->id,
            tenantId: $model->tenant_id,
            name: $model->name,
            code: $model->code,
            status: $model->status,
            email: $model->email,
            phone: $model->phone,
            address: $model->address,
            city: $model->city,
            country: $model->country,
            taxNumber: $model->tax_number,
            currency: $model->currency,
            notes: $model->notes,
        );
    }
}
