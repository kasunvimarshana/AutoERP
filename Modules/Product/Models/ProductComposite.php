<?php

declare(strict_types=1);

namespace Modules\Product\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Modules\Tenant\Contracts\TenantScoped;

/**
 * ProductComposite Model
 *
 * Defines parts of a composite product
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $composite_id
 * @property string $component_id
 * @property string $quantity
 * @property int $sort_order
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class ProductComposite extends Model
{
    use HasUuids, TenantScoped;

    protected $fillable = [
        'tenant_id',
        'composite_id',
        'component_id',
        'quantity',
        'sort_order',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    /**
     * Get the composite product
     */
    public function composite()
    {
        return $this->belongsTo(Product::class, 'composite_id');
    }

    /**
     * Get the component product
     */
    public function component()
    {
        return $this->belongsTo(Product::class, 'component_id');
    }

    /**
     * Alias for component - for API consistency
     */
    public function partProduct()
    {
        return $this->component();
    }
}
