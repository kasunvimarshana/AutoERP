<?php

declare(strict_types=1);

namespace Modules\Product\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Modules\Tenant\Contracts\TenantScoped;

/**
 * ProductBundle Model
 *
 * Defines items in a product bundle
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $bundle_id
 * @property string $product_id
 * @property string $quantity
 * @property int $sort_order
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class ProductBundle extends Model
{
    use HasUuids, TenantScoped;

    protected $fillable = [
        'tenant_id',
        'bundle_id',
        'product_id',
        'quantity',
        'sort_order',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    /**
     * Get the bundle product
     */
    public function bundle()
    {
        return $this->belongsTo(Product::class, 'bundle_id');
    }

    /**
     * Get the item product
     */
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
