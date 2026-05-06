<?php

declare(strict_types=1);

namespace Modules\Sales\Infrastructure\Persistence\Eloquent\Models;

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

class SalesReturnLineModel extends Model
{
    use HasAudit;
    use HasTenant;

    protected $table = 'sales_return_lines';

    protected $fillable = [
        'tenant_id',
        'org_unit_id',
        'row_version',
        'sales_return_id',
        'original_sales_order_line_id',
        'product_id',
        'variant_id',
        'batch_id',
        'serial_id',
        'to_location_id',
        'uom_id',
        'return_qty',
        'unit_price',
        'condition',
        'disposition',
        'restocking_fee',
        'quality_check_notes',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'org_unit_id' => 'integer',
        'row_version' => 'integer',
        'sales_return_id' => 'integer',
        'original_sales_order_line_id' => 'integer',
        'product_id' => 'integer',
        'variant_id' => 'integer',
        'batch_id' => 'integer',
        'serial_id' => 'integer',
        'to_location_id' => 'integer',
        'uom_id' => 'integer',
        'return_qty' => 'decimal:6',
        'unit_price' => 'decimal:6',
        'line_total' => 'decimal:6',
        'restocking_fee' => 'decimal:6',
    ];

    public function salesReturn(): BelongsTo
    {
        return $this->belongsTo(SalesReturnModel::class, 'sales_return_id');
    }

    public function originalSalesOrderLine(): BelongsTo
    {
        return $this->belongsTo(SalesOrderLineModel::class, 'original_sales_order_line_id');
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

    public function toLocation(): BelongsTo
    {
        return $this->belongsTo(WarehouseLocationModel::class, 'to_location_id');
    }

    public function uom(): BelongsTo
    {
        return $this->belongsTo(UnitOfMeasureModel::class, 'uom_id');
    }
}
