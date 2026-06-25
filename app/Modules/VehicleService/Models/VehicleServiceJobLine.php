<?php

declare(strict_types=1);

namespace Modules\VehicleService\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Models\TenantOwnedModel;
use Modules\Inventory\Models\InventoryMovement;
use Modules\Item\Models\Item;
use Modules\Item\Models\ItemVariant;
use Modules\UOM\Models\UnitOfMeasureModel;
use Modules\VehicleService\Enums\VehicleServiceLineSourceType;
use Modules\VehicleService\Enums\VehicleServiceLineStatus;

final class VehicleServiceJobLine extends TenantOwnedModel
{
    protected $table = 'vehicle_service_job_lines';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'vehicle_service_job_id' => 'integer',
            'parent_line_id' => 'integer',
            'line_number' => 'integer',
            'line_source_type' => VehicleServiceLineSourceType::class,
            'item_id' => 'integer',
            'item_variant_id' => 'integer',
            'uom_id' => 'integer',
            'quantity' => 'decimal:6',
            'unit_cost' => 'decimal:6',
            'unit_price' => 'decimal:6',
            'discount_rate' => 'decimal:6',
            'discount_amount' => 'decimal:6',
            'tax_rate' => 'decimal:6',
            'tax_amount' => 'decimal:6',
            'charge_rate' => 'decimal:6',
            'charge_amount' => 'decimal:6',
            'line_total' => 'decimal:6',
            'is_inventory_tracked' => 'boolean',
            'is_customer_supplied' => 'boolean',
            'is_external' => 'boolean',
            'is_billable' => 'boolean',
            'is_employee_assignable' => 'boolean',
            'inventory_movement_id' => 'integer',
            'status' => VehicleServiceLineStatus::class,
        ]);
    }

    public function job(): BelongsTo
    {
        return $this->belongsTo(VehicleServiceJob::class, 'vehicle_service_job_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_line_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_line_id')->orderBy('line_number');
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

    public function employeeAssignments(): HasMany
    {
        return $this->hasMany(VehicleServiceLineEmployee::class, 'vehicle_service_job_line_id');
    }
}
