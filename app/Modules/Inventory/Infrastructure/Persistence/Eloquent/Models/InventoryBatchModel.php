<?php
namespace Modules\Inventory\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;

class InventoryBatchModel extends BaseModel
{
    protected $table = 'inventory_batches';

    protected $casts = [
        'manufacturing_date' => 'date',
        'expiry_date'        => 'date',
        'attributes'         => 'array',
    ];
}
