<?php
namespace Modules\Product\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\Product\Domain\Entities\ProductCategory;
use Modules\Product\Domain\RepositoryInterfaces\ProductCategoryRepositoryInterface;
use Modules\Product\Infrastructure\Persistence\Eloquent\Models\ProductCategoryModel;

class EloquentProductCategoryRepository extends EloquentRepository implements ProductCategoryRepositoryInterface
{
    public function __construct(ProductCategoryModel $model)
    {
        parent::__construct($model);
    }

    public function findById(int $id): ?ProductCategory
    {
        $model = parent::findById($id);
        return $model ? $this->toEntity($model) : null;
    }

    public function findAll(int $tenantId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->newQuery()->where('tenant_id', $tenantId);
        $this->applyFilters($query, $filters);
        return $query->paginate($perPage);
    }

    public function create(array $data): ProductCategory
    {
        $model = parent::create($data);
        return $this->toEntity($model);
    }

    public function update(ProductCategory $category, array $data): ProductCategory
    {
        $model   = $this->model->findOrFail($category->id);
        $updated = parent::update($model, $data);
        return $this->toEntity($updated);
    }

    public function delete(ProductCategory $category): bool
    {
        $model = $this->model->findOrFail($category->id);
        return parent::delete($model);
    }

    private function toEntity(object $model): ProductCategory
    {
        return new ProductCategory(
            id:          $model->id,
            tenantId:    $model->tenant_id,
            name:        $model->name,
            slug:        $model->slug,
            parentId:    $model->parent_id,
            description: $model->description,
            isActive:    (bool) $model->is_active,
        );
    }
}
