<?php

declare(strict_types=1);

namespace Modules\Purchase\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\TenantOwnedModel;
use Modules\Inventory\Models\InventoryBatch;
use Modules\Inventory\Models\InventoryMovement;

final class GoodsReceiptNoteLineBatchAllocation extends TenantOwnedModel
{
    protected $table = 'goods_receipt_note_line_batch_allocations';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'goods_receipt_note_line_id' => 'integer',
            'batch_id' => 'integer',
            'quantity' => 'decimal:6',
            'base_quantity' => 'decimal:6',
            'inventory_movement_id' => 'integer',
        ]);
    }

    public function line(): BelongsTo
    {
        return $this->belongsTo(GoodsReceiptNoteLine::class, 'goods_receipt_note_line_id');
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(InventoryBatch::class, 'batch_id');
    }

    public function inventoryMovement(): BelongsTo
    {
        return $this->belongsTo(InventoryMovement::class, 'inventory_movement_id');
    }
}
