<?php

namespace Modules\Inventory\Infrastructure\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Shared\Infrastructure\Traits\HasTenantScope;

class ProductVariantModel extends Model
{
    use HasUuids, SoftDeletes, HasTenantScope;

    protected $table = 'inventory_product_variants';

    protected $fillable = [
        'id', 'tenant_id', 'product_id', 'sku', 'name',
        'attributes', 'unit_price', 'cost_price', 'barcode_ean13', 'is_active',
    ];

    protected $casts = [
        'attributes' => 'array',
        'is_active'  => 'boolean',
    ];

    public function product(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(ProductModel::class, 'product_id');
    }
}
