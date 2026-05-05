<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Audit\Infrastructure\Persistence\Eloquent\Traits\HasAudit;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\BatchModel;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\SerialModel;
use Modules\Product\Infrastructure\Persistence\Eloquent\Models\ProductModel;
use Modules\Product\Infrastructure\Persistence\Eloquent\Models\ProductVariantModel;
use Modules\Product\Infrastructure\Persistence\Eloquent\Models\UnitOfMeasureModel;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Traits\HasTenant;
use Modules\Warehouse\Infrastructure\Persistence\Eloquent\Models\WarehouseLocationModel;

class StockLevelModel extends Model
{
    use HasAudit;
    use HasTenant;

    protected $table = 'stock_levels';

    protected $fillable = [
        'tenant_id',
        'org_unit_id',
        'row_version',
        'product_id',
        'variant_id',
        'location_id',
        'batch_id',
        'serial_id',
        'uom_id',
        'quantity_on_hand',
        'quantity_reserved',
        'unit_cost',
        'last_movement_at',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'org_unit_id' => 'integer',
        'row_version' => 'integer',
        'product_id' => 'integer',
        'variant_id' => 'integer',
        'location_id' => 'integer',
        'batch_id' => 'integer',
        'serial_id' => 'integer',
        'uom_id' => 'integer',
        'quantity_on_hand' => 'decimal:6',
        'quantity_reserved' => 'decimal:6',
        'unit_cost' => 'decimal:6',
        'last_movement_at' => 'datetime',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(ProductModel::class, 'product_id');
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariantModel::class, 'variant_id');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(WarehouseLocationModel::class, 'location_id');
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(BatchModel::class, 'batch_id');
    }

    public function serial(): BelongsTo
    {
        return $this->belongsTo(SerialModel::class, 'serial_id');
    }

    public function uom(): BelongsTo
    {
        return $this->belongsTo(UnitOfMeasureModel::class, 'uom_id');
    }
}
