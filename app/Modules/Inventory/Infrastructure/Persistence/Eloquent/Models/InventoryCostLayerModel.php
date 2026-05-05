<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Audit\Infrastructure\Persistence\Eloquent\Traits\HasAudit;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Traits\HasTenant;

/**
 * @property int $id
 * @property int $tenant_id
 * @property int $product_id
 * @property int|null $variant_id
 * @property int|null $batch_id
 * @property int $location_id
 * @property string $valuation_method
 * @property string $layer_date
 * @property string $quantity_in
 * @property string $quantity_remaining
 * @property string $unit_cost
 * @property string $total_cost
 * @property string|null $reference_type
 * @property int|null $reference_id
 * @property bool $is_closed
 */
class InventoryCostLayerModel extends Model
{
    use HasAudit;
    use HasTenant;

    protected $table = 'inventory_cost_layers';

    protected $fillable = [
        'tenant_id',
        'org_unit_id',
        'row_version',
        'product_id',
        'variant_id',
        'batch_id',
        'location_id',
        'valuation_method',
        'layer_date',
        'quantity_in',
        'quantity_remaining',
        'unit_cost',
        'reference_type',
        'reference_id',
        'is_closed',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'product_id' => 'integer',
        'variant_id' => 'integer',
        'batch_id' => 'integer',
        'location_id' => 'integer',
        'reference_id' => 'integer',
        'is_closed' => 'boolean',
            'quantity_in' => 'decimal:6',
            'quantity_remaining' => 'decimal:6',
            'unit_cost' => 'decimal:6',
        ];
}
