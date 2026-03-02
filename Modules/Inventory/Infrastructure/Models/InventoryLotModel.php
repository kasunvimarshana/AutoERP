<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InventoryLotModel extends Model
{
    use SoftDeletes;

    protected $table = 'inventory_lots';

    protected $guarded = [];

    protected $casts = [
        'manufactured_date' => 'date:Y-m-d',
        'expiry_date' => 'date:Y-m-d',
        'quantity' => 'decimal:4',
    ];
}
