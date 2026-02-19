<?php

declare(strict_types=1);

namespace Modules\Product\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Modules\Tenant\Contracts\TenantScoped;

/**
 * Unit Model
 *
 * Measurement units for products
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $name
 * @property string $symbol
 * @property string $type
 * @property array $metadata
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class Unit extends Model
{
    use HasUuids, TenantScoped;

    protected $fillable = [
        'tenant_id',
        'name',
        'symbol',
        'type',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    /**
     * Get products using this unit for buying
     */
    public function productsAsBuyingUnit()
    {
        return $this->hasMany(Product::class, 'buying_unit_id');
    }

    /**
     * Get products using this unit for selling
     */
    public function productsAsSellingUnit()
    {
        return $this->hasMany(Product::class, 'selling_unit_id');
    }
}
