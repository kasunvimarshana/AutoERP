<?php

declare(strict_types=1);

namespace Modules\Product\Domain\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Domain\Traits\HasTenant;

/**
 * Product entity.
 *
 * Represents a product in the catalog.
 * Supports multiple types: physical, consumable, service, digital, bundle, composite, variant.
 *
 * UOM rules:
 *   - uom_id (base UOM) is REQUIRED for all inventory tracking.
 *   - buying_uom_id and selling_uom_id are optional; fall back to base UOM when absent.
 */
class Product extends Model
{
    use HasTenant;

    protected $table = 'products';

    protected $fillable = [
        'tenant_id',
        'name',
        'sku',
        'type',
        'description',
        'uom_id',
        'buying_uom_id',
        'selling_uom_id',
        'is_active',
        'has_serial_tracking',
        'has_batch_tracking',
        'has_expiry_tracking',
        'barcode',
    ];

    protected $casts = [
        'is_active'           => 'boolean',
        'has_serial_tracking' => 'boolean',
        'has_batch_tracking'  => 'boolean',
        'has_expiry_tracking' => 'boolean',
    ];

    public function uom(): BelongsTo
    {
        return $this->belongsTo(UnitOfMeasure::class, 'uom_id');
    }

    public function buyingUom(): BelongsTo
    {
        return $this->belongsTo(UnitOfMeasure::class, 'buying_uom_id');
    }

    public function sellingUom(): BelongsTo
    {
        return $this->belongsTo(UnitOfMeasure::class, 'selling_uom_id');
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class);
    }

    public function uomConversions(): HasMany
    {
        return $this->hasMany(UomConversion::class);
    }
}
