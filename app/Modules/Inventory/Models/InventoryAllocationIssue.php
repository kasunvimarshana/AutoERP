<?php

declare(strict_types=1);

namespace Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\CoreModel;

final class InventoryAllocationIssue extends CoreModel
{
    protected $table = 'inventory_allocation_issues';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'allocation_id' => 'integer',
            'allocation_line_id' => 'integer',
            'movement_id' => 'integer',
            'quantity_issued' => 'decimal:6',
            'unit_cost' => 'decimal:6',
            'total_cost' => 'decimal:6',
            'reversal_movement_id' => 'integer',
            'reversed_at' => 'datetime',
        ]);
    }

    public function allocation(): BelongsTo
    {
        return $this->belongsTo(InventoryAllocation::class, 'allocation_id');
    }

    public function allocationLine(): BelongsTo
    {
        return $this->belongsTo(InventoryAllocationLine::class, 'allocation_line_id');
    }

    public function movement(): BelongsTo
    {
        return $this->belongsTo(InventoryMovement::class, 'movement_id');
    }

    public function reversalMovement(): BelongsTo
    {
        return $this->belongsTo(InventoryMovement::class, 'reversal_movement_id');
    }
}
