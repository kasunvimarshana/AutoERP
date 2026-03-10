<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Inventory extends Model
{
    protected $fillable = [
        'product_id',
        'product_name',
        'product_code',
        'product_category',
        'quantity',
        'reserved_quantity',
        'warehouse_location',
        'reorder_level',
    ];

    protected $casts = [
        'product_id'        => 'integer',
        'quantity'          => 'integer',
        'reserved_quantity' => 'integer',
        'reorder_level'     => 'integer',
    ];

    protected $appends = ['available_quantity'];

    public function getAvailableQuantityAttribute(): int
    {
        return $this->quantity - $this->reserved_quantity;
    }
}
