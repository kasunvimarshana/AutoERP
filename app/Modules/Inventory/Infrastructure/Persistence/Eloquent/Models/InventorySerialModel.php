<?php
namespace Modules\Inventory\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;

class InventorySerialModel extends BaseModel
{
    protected $table = 'inventory_serials';

    protected $casts = [
        'warranty_expires_at' => 'datetime',
    ];
}
