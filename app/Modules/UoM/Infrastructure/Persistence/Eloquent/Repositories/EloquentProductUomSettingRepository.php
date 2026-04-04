<?php
namespace Modules\UoM\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\UoM\Domain\Entities\ProductUomSetting;
use Modules\UoM\Domain\RepositoryInterfaces\ProductUomSettingRepositoryInterface;
use Modules\UoM\Infrastructure\Persistence\Eloquent\Models\ProductUomSettingModel;

class EloquentProductUomSettingRepository extends EloquentRepository implements ProductUomSettingRepositoryInterface
{
    public function __construct(ProductUomSettingModel $model)
    {
        parent::__construct($model);
    }

    public function findById(int $id): ?ProductUomSetting
    {
        $model = parent::findById($id);
        return $model ? $this->toEntity($model) : null;
    }

    public function findByProduct(int $productId): ?ProductUomSetting
    {
        $model = $this->model->where('product_id', $productId)->first();
        return $model ? $this->toEntity($model) : null;
    }

    public function create(array $data): ProductUomSetting
    {
        $model = parent::create($data);
        return $this->toEntity($model);
    }

    public function update(ProductUomSetting $setting, array $data): ProductUomSetting
    {
        $model = $this->model->findOrFail($setting->id);
        $updated = parent::update($model, $data);
        return $this->toEntity($updated);
    }

    private function toEntity(ProductUomSettingModel $model): ProductUomSetting
    {
        return new ProductUomSetting(
            id: $model->id,
            productId: $model->product_id,
            baseUomId: $model->base_uom_id,
            purchaseUomId: $model->purchase_uom_id,
            salesUomId: $model->sales_uom_id,
            inventoryUomId: $model->inventory_uom_id,
            purchaseFactor: (float) $model->purchase_factor,
            salesFactor: (float) $model->sales_factor,
            inventoryFactor: (float) $model->inventory_factor,
        );
    }
}
