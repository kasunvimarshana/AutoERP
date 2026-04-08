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
    use HasUuid, HasTenant;

    protected $table = 'products';

    protected $fillable = [
        'tenant_id',
        'category_id',
        'sku',
        'barcode',
        'gtin',
        'gs1_company_prefix',
        'name',
        'description',
        'type',
        'status',
        'unit_of_measure',
        'weight',
        'weight_unit',
        'dimensions_length',
        'dimensions_width',
        'dimensions_height',
        'dimensions_unit',
        'cost_price',
        'selling_price',
        'currency_code',
        'tax_class',
        'tax_rate',
        'is_taxable',
        'is_trackable',
        'is_purchasable',
        'is_sellable',
        'min_stock_level',
        'max_stock_level',
        'reorder_point',
        'reorder_quantity',
        'lead_time_days',
        'image_path',
        'metadata',
    ];

    protected $casts = [
        'cost_price'    => 'decimal:4',
        'selling_price' => 'decimal:4',
        'tax_rate'      => 'decimal:4',
        'is_taxable'    => 'boolean',
        'is_trackable'  => 'boolean',
        'is_purchasable' => 'boolean',
        'is_sellable'   => 'boolean',
        'metadata'      => 'array',
        'created_at'    => 'datetime',
        'updated_at'    => 'datetime',
        'deleted_at'    => 'datetime',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategoryModel::class, 'category_id');
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariantModel::class, 'product_id');
    }

    public function comboItems(): HasMany
    {
        return $this->hasMany(ProductComboItemModel::class, 'combo_product_id');
    }
}
