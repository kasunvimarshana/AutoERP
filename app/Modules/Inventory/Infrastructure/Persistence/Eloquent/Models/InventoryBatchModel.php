<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasAudit;

class InventoryBatchModel extends BaseModel
{
    use HasAudit;

    protected $table = 'inventory_batches';

    protected $fillable = [
        'tenant_id', 'product_id', 'variation_id', 'batch_number', 'lot_number',
        'manufacture_date', 'expiry_date', 'best_before_date', 'supplier_id',
        'supplier_batch_ref', 'initial_qty', 'remaining_qty', 'unit_cost',
        'currency', 'status', 'notes', 'metadata',
    ];

    protected $casts = [
        'tenant_id' => 'integer', 'product_id' => 'integer', 'variation_id' => 'integer',
        'supplier_id' => 'integer', 'initial_qty' => 'float', 'remaining_qty' => 'float',
        'unit_cost' => 'float', 'manufacture_date' => 'datetime',
        'expiry_date' => 'datetime', 'best_before_date' => 'datetime', 'metadata' => 'array',
    ];
}
