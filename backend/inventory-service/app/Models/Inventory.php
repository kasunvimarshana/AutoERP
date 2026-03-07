<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Inventory extends TenantAwareModel
{
    use HasFactory;

    protected $table = 'inventory';

    protected $fillable = [
        'tenant_id',
        'product_id',
        'warehouse_id',
        'quantity',
        'reserved_quantity',
        'reorder_level',
        'max_quantity',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'reserved_quantity' => 'integer',
        'reorder_level' => 'integer',
        'max_quantity' => 'integer',
    ];

    public function warehouse(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function getAvailableQuantityAttribute(): int
    {
        return $this->quantity - $this->reserved_quantity;
    }

    public function isLow(): bool
    {
        return $this->available_quantity <= $this->reorder_level;
    }
}
