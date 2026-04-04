<?php
namespace Modules\Product\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\Product\Domain\Entities\ProductVariant;
use Modules\Product\Domain\RepositoryInterfaces\ProductVariantRepositoryInterface;
use Modules\Product\Infrastructure\Persistence\Eloquent\Models\ProductVariantModel;

class EloquentProductVariantRepository extends EloquentRepository implements ProductVariantRepositoryInterface
{
    public function __construct(ProductVariantModel $model)
    {
        parent::__construct($model);
    }

    public function findById(int $id): ?ProductVariant
    {
        $model = parent::findById($id);
        return $model ? $this->toEntity($model) : null;
    }

    public function findByProduct(int $productId): array
    {
        return $this->model->where('product_id', $productId)
            ->get()
            ->map(fn($m) => $this->toEntity($m))
            ->all();
    }

    public function create(array $data): ProductVariant
    {
        $model = parent::create($data);
        return $this->toEntity($model);
    }

    public function update(ProductVariant $variant, array $data): ProductVariant
    {
        $model   = $this->model->findOrFail($variant->id);
        $updated = parent::update($model, $data);
        return $this->toEntity($updated);
    }

    public function delete(ProductVariant $variant): bool
    {
        $model = $this->model->findOrFail($variant->id);
        return parent::delete($model);
    }

    private function toEntity(object $model): ProductVariant
    {
        return new ProductVariant(
            id:         $model->id,
            productId:  $model->product_id,
            sku:        $model->sku,
            name:       $model->name,
            basePrice:  $model->base_price !== null ? (float) $model->base_price : null,
            costPrice:  $model->cost_price !== null ? (float) $model->cost_price : null,
            barcode:    $model->barcode,
            attributes: $model->attributes,
            isActive:   (bool) $model->is_active,
        );
    }
}
