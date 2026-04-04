<?php
namespace Modules\Inventory\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\Inventory\Domain\Entities\InventorySetting;
use Modules\Inventory\Domain\RepositoryInterfaces\InventorySettingRepositoryInterface;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\InventorySettingModel;

class EloquentInventorySettingRepository extends EloquentRepository implements InventorySettingRepositoryInterface
{
    public function __construct(InventorySettingModel $model)
    {
        parent::__construct($model);
    }

    public function findByTenant(int $tenantId): ?InventorySetting
    {
        $model = $this->model->where('tenant_id', $tenantId)->first();
        return $model ? $this->toEntity($model) : null;
    }

    public function create(array $data): InventorySetting
    {
        $model = parent::create($data);
        return $this->toEntity($model);
    }

    public function update(InventorySetting $setting, array $data): InventorySetting
    {
        $model = $this->model->findOrFail($setting->id);
        $updated = parent::update($model, $data);
        return $this->toEntity($updated);
    }

    public function save(InventorySetting $setting): InventorySetting
    {
        $model = $this->model->findOrFail($setting->id);
        $updated = parent::update($model, [
            'valuation_method'       => $setting->valuationMethod,
            'management_method'      => $setting->managementMethod,
            'stock_rotation_strategy' => $setting->stockRotationStrategy,
            'allocation_algorithm'   => $setting->allocationAlgorithm,
            'cycle_count_method'     => $setting->cycleCountMethod,
            'negative_stock_allowed' => $setting->negativeStockAllowed,
            'auto_reorder_enabled'   => $setting->autoReorderEnabled,
            'default_reorder_point'  => $setting->defaultReorderPoint,
            'default_reorder_qty'    => $setting->defaultReorderQty,
        ]);
        return $this->toEntity($updated);
    }

    private function toEntity(object $model): InventorySetting
    {
        return new InventorySetting(
            id: $model->id,
            tenantId: $model->tenant_id,
            valuationMethod: $model->valuation_method,
            managementMethod: $model->management_method,
            stockRotationStrategy: $model->stock_rotation_strategy,
            allocationAlgorithm: $model->allocation_algorithm,
            cycleCountMethod: $model->cycle_count_method,
            negativeStockAllowed: (bool) $model->negative_stock_allowed,
            autoReorderEnabled: (bool) $model->auto_reorder_enabled,
            defaultReorderPoint: $model->default_reorder_point !== null ? (float) $model->default_reorder_point : null,
            defaultReorderQty: $model->default_reorder_qty !== null ? (float) $model->default_reorder_qty : null,
        );
    }
}
