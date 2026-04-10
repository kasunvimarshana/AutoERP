<?php

namespace App\Modules\Sales\Models;

use BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class DeliveryOrder extends BaseModel
{
    protected $table = 'delivery_orders';

    protected $fillable = [
        'tenant_id',
        'delivery_number',
        'sales_order_id',
        'warehouse_id',
        'status',
        'ship_date',
        'carrier',
        'tracking_number',
        'notes',
        'created_by',
        'created_at'
    ];

    protected $casts = [
        'ship_date' => 'date',
        'created_at' => 'datetime'
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Core\Models\Tenant::class, 'tenant_id');
    }

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Sales\Models\SalesOrder::class, 'sales_order_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Warehouse\Models\Warehouse::class, 'warehouse_id');
    }
}
