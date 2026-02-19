<?php

declare(strict_types=1);

namespace Modules\Product\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Modules\Tenant\Contracts\TenantScoped;

/**
 * ProductUnitConversion Model
 *
 * Defines conversion rates between units for a product
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $product_id
 * @property string $from_unit_id
 * @property string $to_unit_id
 * @property string $conversion_factor
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class ProductUnitConversion extends Model
{
    use HasUuids, TenantScoped;

    protected $fillable = [
        'tenant_id',
        'product_id',
        'from_unit_id',
        'to_unit_id',
        'conversion_factor',
    ];

    protected $casts = [
        'conversion_factor' => 'decimal:10',
    ];

    /**
     * Get the product
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the from unit
     */
    public function fromUnit()
    {
        return $this->belongsTo(Unit::class, 'from_unit_id');
    }

    /**
     * Get the to unit
     */
    public function toUnit()
    {
        return $this->belongsTo(Unit::class, 'to_unit_id');
    }

    /**
     * Convert quantity from one unit to another
     */
    public function convert(string $quantity): string
    {
        return bcmul($quantity, $this->conversion_factor, config('pricing.decimal_scale', 6));
    }
}
