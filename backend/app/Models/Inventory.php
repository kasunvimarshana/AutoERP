<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Inventory extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'product_id',
        'warehouse_id',
        'quantity',
        'reserved_quantity',
        'available_quantity',
        'unit',
        'location',
        'notes',
    ];

    protected $casts = [
        'quantity'           => 'integer',
        'reserved_quantity'  => 'integer',
        'available_quantity' => 'integer',
    ];

    // ─── Relationships ─────────────────────────────────────────────────────

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(InventoryTransaction::class);
    }

    // ─── Helpers ──────────────────────────────────────────────────────────

    public function getAvailableQuantity(): int
    {
        return $this->quantity - $this->reserved_quantity;
    }

    public function canFulfill(int $requiredQty): bool
    {
        return $this->getAvailableQuantity() >= $requiredQty;
    }

    // ─── Scopes ───────────────────────────────────────────────────────────

    public function scopeInStock($query)
    {
        return $query->where('quantity', '>', 0);
    }

    public function scopeLowStock($query, int $threshold = 10)
    {
        return $query->where('quantity', '<=', $threshold)->where('quantity', '>', 0);
    }
}
