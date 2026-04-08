<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasTenant;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasUuid;

class ProductVariantModel extends BaseModel
{
    use HasUuid, HasTenant;

    protected $table = 'product_variants';

    protected $fillable = [
        'tenant_id',
        'product_id',
        'sku',
        'barcode',
        'name',
        'attributes',
        'cost_price',
        'selling_price',
        'weight',
        'image_path',
        'is_active',
        'metadata',
    ];

    protected $casts = [
        'attributes'    => 'array',
        'cost_price'    => 'decimal:4',
        'selling_price' => 'decimal:4',
        'is_active'     => 'boolean',
        'metadata'      => 'array',
        'created_at'    => 'datetime',
        'updated_at'    => 'datetime',
        'deleted_at'    => 'datetime',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(ProductModel::class, 'product_id');
    }
}
