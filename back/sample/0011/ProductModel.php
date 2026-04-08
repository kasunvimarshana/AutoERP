<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * ProductModel — Eloquent model (Infrastructure layer only).
 * The domain entity Product lives separately in Domain/Entities/Product.php.
 * This model is ONLY used by EloquentProductRepository to persist/retrieve.
 */
final class ProductModel extends Model
{
    use SoftDeletes;

    protected $table = 'products';

    protected $fillable = [
        'tenant_id', 'category_id', 'tax_class_id', 'uom_id',
        'name', 'sku', 'barcode', 'barcode_type',
        'description', 'short_description', 'images',
        'type', 'status',
        'is_variable', 'is_composite', 'is_kit',
        'is_stockable', 'track_inventory',
        'track_batches', 'track_lots', 'track_serials', 'track_expiry',
        'shelf_life_days', 'expiry_warning_days',
        'valuation_method', 'standard_cost', 'standard_price',
        'units_of_measure',
        'weight', 'weight_unit', 'length', 'width', 'height', 'dimension_unit',
        'reorder_point', 'reorder_quantity', 'min_stock_level', 'max_stock_level',
        'safety_stock', 'lead_time_days', 'economic_order_qty',
        'download_url', 'download_limit', 'download_expiry_days', 'license_type',
        'subscription_interval', 'subscription_interval_count', 'subscription_trial_days',
        'hs_code', 'country_of_origin',
        'tags', 'attributes', 'metadata', 'seo',
    ];

    protected $casts = [
        'is_variable'   => 'boolean',
        'is_composite'  => 'boolean',
        'is_kit'        => 'boolean',
        'is_stockable'  => 'boolean',
        'track_inventory' => 'boolean',
        'track_batches' => 'boolean',
        'track_lots'    => 'boolean',
        'track_serials' => 'boolean',
        'track_expiry'  => 'boolean',
        'units_of_measure' => 'array',
        'images'        => 'array',
        'tags'          => 'array',
        'attributes'    => 'array',
        'metadata'      => 'array',
        'seo'           => 'array',
    ];

    public function variants()
    {
        return $this->hasMany(
            \Modules\Product\Infrastructure\Persistence\Eloquent\Models\ProductVariantModel::class,
            'product_id'
        );
    }
}
