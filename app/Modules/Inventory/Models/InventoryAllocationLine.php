<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;
use Modules\Core\Models\TenantOwnedModel;
use Modules\Core\Services\DecimalMath;

final class InventoryAllocationLine extends TenantOwnedModel
{
    protected $table = 'inventory_allocation_lines';

    protected $guarded = ['id'];

    protected static function booted(): void
    {
        self::updating(static function (InventoryAllocationLine $line): void {
            if ($line->isDirty(['tenant_id', 'allocation_id', 'stock_balance_id'])) {
                throw new LogicException('Structural keys on InventoryAllocationLine are immutable.');
            }
        });

        self::deleting(static function (InventoryAllocationLine $line): void {
            $math = app(DecimalMath::class);
            if (! $math->isZero((string) ($line->quantity_issued ?? '0.000000'))) {
                throw new LogicException('Inventory allocation lines with issued quantities cannot be deleted.');
            }
        });
    }

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
