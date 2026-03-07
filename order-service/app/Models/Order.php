<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;
    protected $fillable = [
        'customer_name',
        'customer_email',
        'product_sku',
        'product_name',
        'quantity',
        'unit_price',
        'total_price',
        'status',
        'notes',
    ];

    protected $attributes = [
        'status' => 'pending',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
    ];

    /**
     * Check if the order can be cancelled.
     */
    public function isCancellable(): bool
    {
        return in_array($this->status, ['pending', 'confirmed']);
    }

    /**
     * Check if the order can be confirmed.
     */
    public function isConfirmable(): bool
    {
        return $this->status === 'pending';
    }
}
