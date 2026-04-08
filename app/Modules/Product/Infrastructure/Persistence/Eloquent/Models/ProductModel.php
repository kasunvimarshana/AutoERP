<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasTenant;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasUuid;

class ProductModel extends BaseModel
{
    use HasTenant, HasUuid;

    protected $table = 'products';

    protected $fillable = [
        'uuid', 'tenant_id', 'category_id', 'unit_of_measure_id', 'sku', 'barcode',
        'name', 'slug', 'short_description', 'description', 'type', 'status',
        'is_purchasable', 'is_sellable', 'is_stockable', 'has_variants',
        'has_serial_tracking', 'has_batch_tracking', 'has_expiry_tracking',
        'cost_price', 'selling_price', 'min_selling_price', 'currency', 'tax_class',
        'weight', 'weight_unit', 'dimensions', 'images', 'tags', 'metadata',
    ];

    protected $casts = [
        'dimensions'          => 'array',
        'images'              => 'array',
        'tags'                => 'array',
        'metadata'            => 'array',
        'has_variants'        => 'boolean',
        'has_serial_tracking' => 'boolean',
        'has_batch_tracking'  => 'boolean',
        'has_expiry_tracking' => 'boolean',
        'is_purchasable'      => 'boolean',
        'is_sellable'         => 'boolean',
        'is_stockable'        => 'boolean',
        'cost_price'          => 'decimal:6',
        'selling_price'       => 'decimal:6',
        'min_selling_price'   => 'decimal:6',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(CategoryModel::class, 'category_id');
    }

    public function unitOfMeasure(): BelongsTo
    {
        return $this->belongsTo(UnitOfMeasureModel::class, 'unit_of_measure_id');
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariantModel::class, 'product_id');
    }
}
