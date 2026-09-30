<?php

declare(strict_types=1);

namespace Modules\Selling\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Models\TenantOwnedModel;
use Modules\Inventory\Models\InventoryBatch;
use Modules\Inventory\Models\InventoryMovement;
use Modules\Inventory\Models\InventorySerialNumber;
use Modules\Item\Models\Item;
use Modules\Item\Models\ItemVariant;
use Modules\OrganizationUnit\Models\OrganizationUnitModel;
use Modules\Tenant\Models\TenantModel;
use Modules\UOM\Models\UnitOfMeasureModel;

final class SaleLine extends TenantOwnedModel
{
    protected $table = 'sale_lines';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'sale_id' => 'integer',
            'line_number' => 'integer',
            'item_id' => 'integer',
            'item_variant_id' => 'integer',
            'uom_id' => 'integer',
            'batch_id' => 'integer',
            'serial_number_id' => 'integer',
            'quantity' => 'decimal:6',
            'base_quantity' => 'decimal:6',
            'unit_price' => 'decimal:6',
            'unit_cost_snapshot' => 'decimal:6',
            'line_total' => 'decimal:6',
            'inventory_movement_id' => 'integer',
        ]);
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class, 'sale_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'item_id');
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ItemVariant::class, 'item_variant_id');
    }

    public function uom(): BelongsTo
    {
        return $this->belongsTo(UnitOfMeasureModel::class, 'uom_id');
    }

    public function inventoryMovement(): BelongsTo
    {
        return $this->belongsTo(InventoryMovement::class, 'inventory_movement_id');
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(InventoryBatch::class, 'batch_id');
    }

    public function serialNumber(): BelongsTo
    {
        return $this->belongsTo(InventorySerialNumber::class, 'serial_number_id');
    }

    public function returns(): HasMany
    {
        return $this->hasMany(SaleReturnLine::class, 'sale_line_id');
    }

    public function organizationUnit(): BelongsTo
    {
        return $this->belongsTo(OrganizationUnitModel::class, 'organization_unit_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(TenantModel::class, 'tenant_id');
    }
}
