<?php

declare(strict_types=1);

namespace Modules\Supplier\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Audit\Infrastructure\Persistence\Eloquent\Traits\HasAudit;
use Modules\Product\Infrastructure\Persistence\Eloquent\Models\ProductModel;
use Modules\Product\Infrastructure\Persistence\Eloquent\Models\ProductVariantModel;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Traits\HasTenant;

class SupplierProductModel extends Model
{
    use HasAudit;
    use HasTenant;

    protected $table = 'supplier_products';

    protected $fillable = [
        'tenant_id',
        'org_unit_id',
        'row_version',
        'supplier_id',
        'product_id',
        'variant_id',
        'supplier_sku',
        'lead_time_days',
        'min_order_qty',
        'is_preferred',
        'last_purchase_price',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'org_unit_id' => 'integer',
        'row_version' => 'integer',
        'supplier_id' => 'integer',
        'product_id' => 'integer',
        'variant_id' => 'integer',
        'lead_time_days' => 'integer',
        'min_order_qty' => 'decimal:6',
        'is_preferred' => 'boolean',
        'last_purchase_price' => 'decimal:6',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(SupplierModel::class, 'supplier_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(ProductModel::class, 'product_id');
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariantModel::class, 'variant_id');
    }
}
