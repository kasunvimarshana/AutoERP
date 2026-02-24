<?php

namespace Modules\Inventory\Infrastructure\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Shared\Infrastructure\Traits\HasTenantScope;

class InventoryLotModel extends Model
{
    use HasUuids, SoftDeletes, HasTenantScope;

    protected $table = 'inventory_lots';

    protected $fillable = [
        'id',
        'tenant_id',
        'product_id',
        'lot_number',
        'tracking_type',
        'qty',
        'status',
        'manufacture_date',
        'expiry_date',
        'notes',
    ];

    protected $casts = [
        'qty' => 'string',
    ];
}
