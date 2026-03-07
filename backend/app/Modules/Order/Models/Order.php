<?php

namespace App\Modules\Order\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use HasFactory, SoftDeletes, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'order_number',
        'customer_name',
        'customer_email',
        'status',
        'subtotal',
        'tax',
        'total',
        'notes',
        'metadata',
        'saga_state',
    ];

    protected $casts = [
        'subtotal'   => 'decimal:2',
        'tax'        => 'decimal:2',
        'total'      => 'decimal:2',
        'metadata'   => 'array',
        'saga_state' => 'array',
    ];

    public function items(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
}
