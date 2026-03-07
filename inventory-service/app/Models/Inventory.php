<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Inventory extends Model
{
    use HasFactory;
    protected $fillable = [
        'product_name',
        'sku',
        'quantity',
        'reserved_quantity',
        'unit_price',
        'status',
    ];

    protected $attributes = [
        'reserved_quantity' => 0,
        'status' => 'active',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'reserved_quantity' => 'integer',
        'unit_price' => 'decimal:2',
    ];

    /**
     * Get the available quantity (total minus reserved).
     */
    public function getAvailableQuantityAttribute(): int
    {
        return $this->quantity - $this->reserved_quantity;
    }
}
