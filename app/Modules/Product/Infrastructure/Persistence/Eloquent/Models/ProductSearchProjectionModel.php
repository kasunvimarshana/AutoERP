<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Traits\HasTenant;

class ProductSearchProjectionModel extends BaseModel
{
    use HasTenant;

    protected $table = 'product_search_projections';

    protected $fillable = [
        'tenant_id',
        'product_id',
        'variant_id',
        'variant_key',
        'product_name',
        'product_slug',
        'product_sku',
        'variant_name',
        'variant_sku',
        'category_id',
        'category_name',
        'brand_id',
        'brand_name',
        'base_uom_id',
        'purchase_uom_id',
        'sales_uom_id',
        'is_active_product',
        'is_active_variant',
        'identifiers_text',
        'identifiers_json',
        'variant_attributes_json',
        'batch_lot_text',
        'stock_on_hand',
        'stock_reserved',
        'stock_available',
        'stock_by_warehouse_json',
        'searchable_text',
        'source_updated_at',
        'last_projected_at',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'product_id' => 'integer',
        'variant_id' => 'integer',
        'variant_key' => 'integer',
        'category_id' => 'integer',
        'brand_id' => 'integer',
        'base_uom_id' => 'integer',
        'purchase_uom_id' => 'integer',
        'sales_uom_id' => 'integer',
        'is_active_product' => 'boolean',
        'is_active_variant' => 'boolean',
        'identifiers_json' => 'array',
        'variant_attributes_json' => 'array',
        'stock_by_warehouse_json' => 'array',
        'stock_on_hand' => 'decimal:6',
        'stock_reserved' => 'decimal:6',
        'stock_available' => 'decimal:6',
        'source_updated_at' => 'datetime',
        'last_projected_at' => 'datetime',
    ];
}
