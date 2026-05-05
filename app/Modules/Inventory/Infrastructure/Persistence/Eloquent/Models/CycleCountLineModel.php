<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Audit\Infrastructure\Persistence\Eloquent\Traits\HasAudit;
use Modules\OrganizationUnit\Infrastructure\Persistence\Eloquent\Models\OrganizationUnitModel;
use Modules\Product\Infrastructure\Persistence\Eloquent\Models\ProductModel;
use Modules\Product\Infrastructure\Persistence\Eloquent\Models\ProductVariantModel;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Traits\HasTenant;

class CycleCountLineModel extends Model
{
    use HasAudit;
    use HasTenant;

    protected $table = 'cycle_count_lines';

    protected $fillable = [
        'tenant_id',
        'org_unit_id',
        'row_version',
        'count_header_id',
        'product_id',
        'variant_id',
        'batch_id',
        'serial_id',
        'system_qty',
        'counted_qty',
        'variance_qty',
        'unit_cost',
        'variance_value',
        'adjustment_movement_id',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'org_unit_id' => 'integer',
        'row_version' => 'integer',
        'count_header_id' => 'integer',
        'product_id' => 'integer',
        'variant_id' => 'integer',
        'batch_id' => 'integer',
        'serial_id' => 'integer',
        'adjustment_movement_id' => 'integer',
        'system_qty' => 'decimal:6',
        'counted_qty' => 'decimal:6',
        'variance_qty' => 'decimal:6',
        'unit_cost' => 'decimal:6',
        'variance_value' => 'decimal:6',
    ];

    public function header(): BelongsTo
    {
        return $this->belongsTo(CycleCountHeaderModel::class, 'count_header_id');
    }

    public function organizationUnit(): BelongsTo
    {
        return $this->belongsTo(OrganizationUnitModel::class, 'org_unit_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(ProductModel::class, 'product_id');
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariantModel::class, 'variant_id');
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(BatchModel::class, 'batch_id');
    }

    public function serial(): BelongsTo
    {
        return $this->belongsTo(SerialModel::class, 'serial_id');
    }

    public function adjustmentMovement(): BelongsTo
    {
        return $this->belongsTo(StockMovementModel::class, 'adjustment_movement_id');
    }
}
