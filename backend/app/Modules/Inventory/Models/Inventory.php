<?php

namespace App\Modules\Inventory\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Inventory extends Model
{
    use HasFactory, SoftDeletes, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'product_id',
        'warehouse_location',
        'quantity',
        'reserved_quantity',
        'reorder_point',
        'reorder_quantity',
        'last_restocked_at',
    ];

    protected $casts = [
        'quantity'          => 'integer',
        'reserved_quantity' => 'integer',
        'reorder_point'     => 'integer',
        'reorder_quantity'  => 'integer',
        'last_restocked_at' => 'datetime',
    ];

    public function product(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Modules\Product\Models\Product::class);
    }

    public function getAvailableQuantityAttribute(): int
    {
        return $this->quantity - $this->reserved_quantity;
    }
}
