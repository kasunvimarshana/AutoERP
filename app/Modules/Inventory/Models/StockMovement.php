<?php

namespace App\Modules\Inventory\Models;

use App\Core\Traits\TenantScoped;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Stock Movement Model
 *
 * Tracks all stock movements (in, out, transfers, adjustments)
 */
class StockMovement extends Model
{
    use TenantScoped;

    protected $fillable = [
        'tenant_id',
        'product_id',
        'warehouse_id',
        'created_by',
        'type',
        'quantity',
        'reference_type',
        'reference_id',
        'reference_number',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'metadata' => 'array',
    ];

    /**
     * Get the tenant that owns the stock movement
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Tenancy\Models\Tenant::class);
    }

    /**
     * Get the product
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the warehouse
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Get the user who created this movement
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
