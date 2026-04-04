<?php
namespace Modules\Product\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\Product\Domain\Entities\Product;
use Modules\Product\Domain\RepositoryInterfaces\ProductRepositoryInterface;
use Modules\Product\Infrastructure\Persistence\Eloquent\Models\ProductModel;

class EloquentProductRepository extends EloquentRepository implements ProductRepositoryInterface
{
    public function __construct(ProductModel $model)
    {
        parent::__construct($model);
    }

    public function findById(int $id): ?Product
    {
        $model = parent::findById($id);
        return $model ? $this->toEntity($model) : null;
    }

    public function findBySku(int $tenantId, string $sku): ?Product
    {
        $model = $this->model->where('tenant_id', $tenantId)->where('sku', $sku)->first();
        return $model ? $this->toEntity($model) : null;
    }

    public function findByBarcode(int $tenantId, string $barcode): ?Product
    {
        $model = $this->model->where('tenant_id', $tenantId)->where('barcode', $barcode)->first();
        return $model ? $this->toEntity($model) : null;
    }

    public function findAll(int $tenantId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->newQuery()->where('tenant_id', $tenantId);
        $this->applyFilters($query, $filters);
        return $query->paginate($perPage);
    }

    public function create(array $data): Product
    {
        $model = parent::create($data);
        return $this->toEntity($model);
    }

    public function update(Product $product, array $data): Product
    {
        $model   = $this->model->findOrFail($product->id);
        $updated = parent::update($model, $data);
        return $this->toEntity($updated);
    }

    public function delete(Product $product): bool
    {
        $model = $this->model->findOrFail($product->id);
        return parent::delete($model);
    }

    private function toEntity(object $model): Product
    {
        return new Product(
            id:             $model->id,
            tenantId:       $model->tenant_id,
            sku:            $model->sku,
            name:           $model->name,
            type:           $model->type,
            status:         $model->status,
            categoryId:     $model->category_id,
            description:    $model->description,
            barcode:        $model->barcode,
            basePrice:      $model->base_price !== null ? (float) $model->base_price : null,
            costPrice:      $model->cost_price !== null ? (float) $model->cost_price : null,
            baseUomId:      $model->base_uom_id,
            trackInventory: (bool) $model->track_inventory,
            trackBatch:     (bool) $model->track_batch,
            trackSerial:    (bool) $model->track_serial,
            trackLot:       (bool) $model->track_lot,
            attributes:     $model->attributes,
        );
    }
}
