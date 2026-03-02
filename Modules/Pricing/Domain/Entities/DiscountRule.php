<?php

declare(strict_types=1);

namespace Modules\Pricing\Domain\Entities;

use Illuminate\Database\Eloquent\Model;
use Modules\Core\Domain\Traits\HasTenant;

/**
 * DiscountRule entity.
 *
 * Represents a configurable discount rule for a tenant.
 * Supports flat (fixed) and percentage discount types.
 * Monetary and quantity columns are cast as string for BCMath-safe arithmetic — never float.
 */
class DiscountRule extends Model
{
    use HasTenant;

    protected $table = 'discount_rules';

    protected $fillable = [
        'tenant_id',
        'name',
        'discount_type',
        'discount_value',
        'apply_to',
        'product_id',
        'customer_tier',
        'location_id',
        'min_quantity',
        'valid_from',
        'valid_to',
        'is_active',
    ];

    /**
     * Cast monetary and quantity columns as string for BCMath precision — never cast to float.
     */
    protected $casts = [
        'discount_value' => 'string',
        'min_quantity'   => 'string',
        'valid_from'     => 'date',
        'valid_to'       => 'date',
        'is_active'      => 'boolean',
    ];
}
