<?php

declare(strict_types=1);

namespace Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Models\TenantOwnedModel;
use Modules\Inventory\Enums\TransferStatus;
use Modules\Warehouse\Models\WarehouseLocationModel;
use Modules\Warehouse\Models\WarehouseModel;

final class InventoryTransfer extends TenantOwnedModel
{
    use SoftDeletes;

    protected $table = 'inventory_transfers';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'transfer_date' => 'date',
            'from_warehouse_id' => 'integer',
            'from_warehouse_location_id' => 'integer',
            'to_warehouse_id' => 'integer',
            'to_warehouse_location_id' => 'integer',
            'status' => TransferStatus::class,
            'approved_at' => 'datetime',
            'posted_at' => 'datetime',
            'dispatched_at' => 'datetime',
            'received_at' => 'datetime',
            'reversed_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ]);
    }

    public function fromWarehouse(): BelongsTo
    {
        return $this->belongsTo(WarehouseModel::class, 'from_warehouse_id');
    }

    public function fromWarehouseLocation(): BelongsTo
    {
        return $this->belongsTo(WarehouseLocationModel::class, 'from_warehouse_location_id');
    }

    public function toWarehouse(): BelongsTo
    {
        return $this->belongsTo(WarehouseModel::class, 'to_warehouse_id');
    }

    public function toWarehouseLocation(): BelongsTo
    {
        return $this->belongsTo(WarehouseLocationModel::class, 'to_warehouse_location_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(InventoryTransferLine::class, 'inventory_transfer_id');
    }
}
