<?php

declare(strict_types=1);

namespace Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Models\CoreModel;

final class InventoryAllocationLine extends CoreModel
{
    protected $table = 'inventory_allocation_lines';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'allocation_id' => 'integer',
            'stock_balance_id' => 'integer',
            'batch_id' => 'integer',
            'serial_number_id' => 'integer',
            'quantity_allocated' => 'decimal:6',
            'quantity_issued' => 'decimal:6',
            'quantity_reversed' => 'decimal:6',
            'quantity_released' => 'decimal:6',
            'quantity_remaining' => 'decimal:6',
        ]);
    }

    public function allocation(): BelongsTo
    {
        return $this->belongsTo(InventoryAllocation::class, 'allocation_id');
    }

    public function stockBalance(): BelongsTo
    {
        return $this->belongsTo(InventoryStockBalance::class, 'stock_balance_id');
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(InventoryBatch::class, 'batch_id');
    }

    public function serialNumber(): BelongsTo
    {
        return $this->belongsTo(InventorySerialNumber::class, 'serial_number_id');
    }

    public function issues(): HasMany
    {
        return $this->hasMany(InventoryAllocationIssue::class, 'allocation_line_id');
    }
}
