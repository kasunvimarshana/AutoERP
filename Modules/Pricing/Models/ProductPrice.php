<?php

declare(strict_types=1);

namespace Modules\Pricing\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Modules\Pricing\Enums\PricingStrategy;
use Modules\Product\Models\Product;
use Modules\Tenant\Contracts\TenantScoped;

/**
 * ProductPrice Model
 *
 * Location-based pricing with multiple strategies
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $product_id
 * @property string|null $location_id
 * @property PricingStrategy $strategy
 * @property string $price
 * @property array $config
 * @property \Carbon\Carbon|null $valid_from
 * @property \Carbon\Carbon|null $valid_until
 * @property bool $is_active
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class ProductPrice extends Model
{
    use HasUuids, TenantScoped;

    protected $fillable = [
        'tenant_id',
        'product_id',
        'location_id',
        'strategy',
        'price',
        'config',
        'valid_from',
        'valid_until',
        'is_active',
    ];

    protected $casts = [
        'strategy' => PricingStrategy::class,
        'config' => 'array',
        'valid_from' => 'datetime',
        'valid_until' => 'datetime',
        'is_active' => 'boolean',
    ];

    /**
     * Get the product
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the location
     */
    public function location()
    {
        return $this->belongsTo(\Modules\Tenant\Models\Organization::class, 'location_id');
    }

    /**
     * Scope active prices
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('valid_from')
                    ->orWhere('valid_from', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('valid_until')
                    ->orWhere('valid_until', '>=', now());
            });
    }

    /**
     * Scope by location
     */
    public function scopeForLocation($query, ?string $locationId)
    {
        return $query->where(function ($q) use ($locationId) {
            $q->whereNull('location_id')
                ->orWhere('location_id', $locationId);
        });
    }
}
