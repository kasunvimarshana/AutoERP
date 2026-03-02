<?php

declare(strict_types=1);

namespace Modules\Pricing\Domain\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Domain\Traits\HasTenant;

/**
 * PriceList entity.
 *
 * Represents a named price list for a tenant.
 * A tenant may have multiple price lists in different currencies.
 */
class PriceList extends Model
{
    use HasTenant;

    protected $table = 'price_lists';

    protected $fillable = [
        'tenant_id',
        'name',
        'currency_code',
        'is_default',
        'is_active',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_active'  => 'boolean',
    ];

    public function productPrices(): HasMany
    {
        return $this->hasMany(ProductPrice::class, 'price_list_id');
    }
}
