<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasTenant;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasUuid;

class StockReservationModel extends BaseModel
{
    use HasUuid, HasTenant;

    protected $table = 'stock_reservations';

    protected $fillable = [
        'tenant_id',
        'product_id',
        'variant_id',
        'warehouse_id',
        'location_id',
        'quantity',
        'reference_type',
        'reference_id',
        'status',
        'expires_at',
    ];

    protected $casts = [
        'quantity'   => 'decimal:4',
        'expires_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];
}
