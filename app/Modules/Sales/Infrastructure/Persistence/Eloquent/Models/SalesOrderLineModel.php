<?php

declare(strict_types=1);

namespace Modules\Sales\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Audit\Infrastructure\Persistence\Eloquent\Traits\HasAudit;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Models\AccountModel;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\BatchModel;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\SerialModel;
use Modules\Product\Infrastructure\Persistence\Eloquent\Models\ProductModel;
use Modules\Product\Infrastructure\Persistence\Eloquent\Models\ProductVariantModel;
use Modules\Product\Infrastructure\Persistence\Eloquent\Models\UnitOfMeasureModel;
use Modules\Tax\Infrastructure\Persistence\Eloquent\Models\TaxGroupModel;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Traits\HasTenant;

class SalesOrderLineModel extends Model
{
    use HasAudit;
    use HasTenant;

    protected $table = 'sales_order_lines';

    protected $fillable = [
        'tenant_id',
        'org_unit_id',
        'row_version',
        'sales_order_id',
        'product_id',
        'variant_id',
        'description',
        'uom_id',
        'ordered_qty',
        'shipped_qty',
        'reserved_qty',
        'unit_price',
        'discount_pct',
        'tax_group_id',
        'line_total',
        'income_account_id',
        'batch_id',
        'serial_id',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'org_unit_id' => 'integer',
        'row_version' => 'integer',
        'sales_order_id' => 'integer',
        'product_id' => 'integer',
        'variant_id' => 'integer',
        'uom_id' => 'integer',
        'tax_group_id' => 'integer',
        'income_account_id' => 'integer',
        'batch_id' => 'integer',
        'serial_id' => 'integer',
        'ordered_qty' => 'decimal:6',
        'shipped_qty' => 'decimal:6',
        'reserved_qty' => 'decimal:6',
        'unit_price' => 'decimal:6',
        'discount_pct' => 'decimal:6',
        'line_total' => 'decimal:6',
    ];

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrderModel::class, 'sales_order_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(ProductModel::class, 'product_id');
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariantModel::class, 'variant_id');
    }

    public function uom(): BelongsTo
    {
        return $this->belongsTo(UnitOfMeasureModel::class, 'uom_id');
    }

    public function taxGroup(): BelongsTo
    {
        return $this->belongsTo(TaxGroupModel::class, 'tax_group_id');
    }

    public function incomeAccount(): BelongsTo
    {
        return $this->belongsTo(AccountModel::class, 'income_account_id');
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(BatchModel::class, 'batch_id');
    }

    public function serial(): BelongsTo
    {
        return $this->belongsTo(SerialModel::class, 'serial_id');
    }
}
