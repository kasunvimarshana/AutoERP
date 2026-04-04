<?php
namespace Modules\UoM\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\UoM\Domain\Entities\UnitOfMeasure;
use Modules\UoM\Domain\RepositoryInterfaces\UnitOfMeasureRepositoryInterface;
use Modules\UoM\Infrastructure\Persistence\Eloquent\Models\UnitOfMeasureModel;

class EloquentUnitOfMeasureRepository extends EloquentRepository implements UnitOfMeasureRepositoryInterface
{
    public function __construct(UnitOfMeasureModel $model)
    {
        parent::__construct($model);
    }

    public function findById(int $id): ?UnitOfMeasure
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

    public function findByCategory(int $categoryId): array
    {
        return $this->model->where('category_id', $categoryId)->get()
            ->map(fn($m) => $this->toEntity($m))
            ->all();
    }

    public function create(array $data): UnitOfMeasure
    {
        $model = parent::create($data);
        return $this->toEntity($model);
    }

    public function update(UnitOfMeasure $uom, array $data): UnitOfMeasure
    {
        $model = $this->model->findOrFail($uom->id);
        $updated = parent::update($model, $data);
        return $this->toEntity($updated);
    }

    public function delete(UnitOfMeasure $uom): bool
    {
        $model = $this->model->findOrFail($uom->id);
        return parent::delete($model);
    }

    private function toEntity(UnitOfMeasureModel $model): UnitOfMeasure
    {
        return new UnitOfMeasure(
            id: $model->id,
            tenantId: $model->tenant_id,
            categoryId: $model->category_id,
            name: $model->name,
            symbol: $model->symbol,
            conversionFactor: (float) $model->conversion_factor,
            isBase: (bool) $model->is_base,
            isActive: (bool) $model->is_active,
        );
    }
}
