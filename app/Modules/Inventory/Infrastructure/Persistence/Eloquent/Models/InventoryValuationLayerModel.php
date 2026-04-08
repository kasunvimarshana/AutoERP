<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasAudit;

class InventoryValuationLayerModel extends BaseModel
{
    use HasAudit;

    protected $table = 'inventory_valuation_layers';

    protected $fillable = [
        'tenant_id', 'product_id', 'variation_id', 'batch_id', 'location_id',
        'layer_date', 'qty_in', 'qty_remaining', 'unit_cost', 'currency',
        'valuation_method', 'reference_type', 'reference_id', 'is_closed', 'metadata',
    ];

    protected $casts = [
        'tenant_id' => 'integer', 'product_id' => 'integer', 'variation_id' => 'integer',
        'batch_id' => 'integer', 'location_id' => 'integer', 'reference_id' => 'integer',
        'qty_in' => 'float', 'qty_remaining' => 'float', 'unit_cost' => 'float',
        'layer_date' => 'date', 'is_closed' => 'boolean', 'metadata' => 'array',
    ];
}
