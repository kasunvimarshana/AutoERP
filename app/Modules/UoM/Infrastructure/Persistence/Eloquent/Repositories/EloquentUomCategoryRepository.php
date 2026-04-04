<?php
namespace Modules\UoM\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\UoM\Domain\Entities\UomCategory;
use Modules\UoM\Domain\RepositoryInterfaces\UomCategoryRepositoryInterface;
use Modules\UoM\Infrastructure\Persistence\Eloquent\Models\UomCategoryModel;

class EloquentUomCategoryRepository extends EloquentRepository implements UomCategoryRepositoryInterface
{
    public function __construct(UomCategoryModel $model)
    {
        parent::__construct($model);
    }

    public function findById(int $id): ?UomCategory
    {
        $model = parent::findById($id);
        return $model ? $this->toEntity($model) : null;
    }

    public function findAll(int $tenantId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->where('tenant_id', $tenantId);
        $this->applyFilters($query, $filters);
        $paginator = $query->paginate($perPage);
        $paginator->getCollection()->transform(fn($m) => $this->toEntity($m));
        return $paginator;
    }

    public function create(array $data): UomCategory
    {
        $model = parent::create($data);
        return $this->toEntity($model);
    }

    public function update(UomCategory $category, array $data): UomCategory
    {
        $model = $this->model->findOrFail($category->id);
        $updated = parent::update($model, $data);
        return $this->toEntity($updated);
    }

    public function delete(UomCategory $category): bool
    {
        $model = $this->model->findOrFail($category->id);
        return parent::delete($model);
    }

    private function toEntity(UomCategoryModel $model): UomCategory
    {
        return new UomCategory(
            id: $model->id,
            tenantId: $model->tenant_id,
            name: $model->name,
            measureType: $model->measure_type,
            isActive: (bool) $model->is_active,
            description: $model->description,
        );
    }
}
