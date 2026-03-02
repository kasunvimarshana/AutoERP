<?php

declare(strict_types=1);

namespace Modules\Product\Domain\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Domain\Traits\HasTenant;

/**
 * UomConversion entity.
 *
 * Stores the product-specific UOM conversion factor.
 * The factor is stored and cast as a string to enable BCMath arithmetic — never float.
 *
 * Direct path: from_uom → to_uom via factor
 * Inverse path: to_uom → from_uom via (1 / factor)
 */
class UomConversion extends Model
{
    use HasTenant;

    protected $table = 'uom_conversions';

    protected $fillable = [
        'tenant_id',
        'product_id',
        'from_uom_id',
        'to_uom_id',
        'factor',
    ];

    /**
     * Cast factor to string to preserve full decimal precision for BCMath.
     * NEVER cast to float.
     */
    protected $casts = [
        'factor' => 'string',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function fromUom(): BelongsTo
    {
        return $this->belongsTo(UnitOfMeasure::class, 'from_uom_id');
    }

    public function toUom(): BelongsTo
    {
        return $this->belongsTo(UnitOfMeasure::class, 'to_uom_id');
    }
}
