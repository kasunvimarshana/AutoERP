<?php
declare(strict_types=1);
namespace Modules\Product\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Product\Domain\Entities\ProductComponent;
use Modules\Product\Domain\RepositoryInterfaces\ProductComponentRepositoryInterface;
use Modules\Product\Infrastructure\Persistence\Eloquent\Models\ProductComponentModel;

class EloquentProductComponentRepository implements ProductComponentRepositoryInterface
{
    public function __construct(private readonly ProductComponentModel $model) {}

    private function toEntity(ProductComponentModel $m): ProductComponent
    {
        return new ProductComponent(
            $m->id, $m->tenant_id, $m->parent_product_id, $m->component_product_id,
            (float)$m->quantity, $m->unit ?? 'each', (bool)$m->is_optional,
            $m->created_at, $m->updated_at,
        );
    }

    public function findById(int $id): ?ProductComponent
    {
        $m = $this->model->newQuery()->find($id);
        return $m ? $this->toEntity($m) : null;
    }

    public function findByParent(int $tenantId, int $parentProductId): array
    {
        return $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('parent_product_id', $parentProductId)
            ->get()
            ->map(fn($m) => $this->toEntity($m))
            ->all();
    }

    public function create(array $data): ProductComponent
    {
        $m = $this->model->newQuery()->create($data);
        return $this->findById($m->id);
    }

    public function update(int $id, array $data): ?ProductComponent
    {
        $m = $this->model->newQuery()->find($id);
        if (!$m) return null;
        $m->update($data);
        return $this->findById($id);
    }

    public function delete(int $id): bool
    {
        $m = $this->model->newQuery()->find($id);
        return $m ? (bool)$m->delete() : false;
    }

    public function deleteByParent(int $tenantId, int $parentProductId): int
    {
        return $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('parent_product_id', $parentProductId)
            ->delete();
    }
}
