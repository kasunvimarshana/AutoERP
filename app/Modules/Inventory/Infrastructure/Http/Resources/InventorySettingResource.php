<?php
namespace Modules\Inventory\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Inventory\Domain\Entities\InventorySetting;

class InventorySettingResource extends JsonResource
{
    public function __construct(private readonly InventorySetting $setting)
    {
        parent::__construct($setting);
    }

    public function toArray($request): array
    {
        return [
            'id'                      => $this->setting->id,
            'tenant_id'               => $this->setting->tenantId,
            'valuation_method'        => $this->setting->valuationMethod,
            'management_method'       => $this->setting->managementMethod,
            'stock_rotation_strategy' => $this->setting->stockRotationStrategy,
            'allocation_algorithm'    => $this->setting->allocationAlgorithm,
            'cycle_count_method'      => $this->setting->cycleCountMethod,
            'negative_stock_allowed'  => $this->setting->negativeStockAllowed,
            'auto_reorder_enabled'    => $this->setting->autoReorderEnabled,
            'default_reorder_point'   => $this->setting->defaultReorderPoint,
            'default_reorder_qty'     => $this->setting->defaultReorderQty,
        ];
    }
}
