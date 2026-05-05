<?php

declare(strict_types=1);

namespace Modules\Supplier\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Audit\Infrastructure\Persistence\Eloquent\Traits\HasAudit;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Traits\HasTenant;

class SupplierProductModel extends Model
{
    use HasAudit;
    use HasTenant;

    protected $table = 'supplier_products';

    protected $fillable = [
        'tenant_id',
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
}
