<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Concerns\HasOrganizationUnitScope;
use Modules\Core\Infrastructure\Persistence\Eloquent\Concerns\HasStatusScope;
use Modules\Core\Infrastructure\Persistence\Eloquent\Concerns\HasTenantScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\OrganizationUnit\Infrastructure\Persistence\Eloquent\Models\OrganizationUnitModel;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Models\TenantModel;
use Modules\User\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Modules\Warehouse\Infrastructure\Persistence\Eloquent\Models\WarehouseLocationModel;
use Modules\Warehouse\Infrastructure\Persistence\Eloquent\Models\WarehouseModel;

class StockTransferModel extends Model
{
    use HasOrganizationUnitScope, HasStatusScope, HasTenantScope;

    protected $table = 'stock_transfers';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'approved_by' => 'integer',
            'from_location_id' => 'integer',
            'from_warehouse_id' => 'integer',
            'metadata' => 'array',
            'organization_unit_id' => 'integer',
            'requested_by' => 'integer',
            'row_version' => 'integer',
            'tenant_id' => 'integer',
            'to_location_id' => 'integer',
            'to_warehouse_id' => 'integer',
            'transferred_at' => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(TenantModel::class, 'tenant_id');
    }

    public function organizationUnit(): BelongsTo
    {
        return $this->belongsTo(OrganizationUnitModel::class, 'organization_unit_id');
    }

    public function fromWarehouse(): BelongsTo
    {
        return $this->belongsTo(WarehouseModel::class, 'from_warehouse_id');
    }

    public function toWarehouse(): BelongsTo
    {
        return $this->belongsTo(WarehouseModel::class, 'to_warehouse_id');
    }

    public function fromLocation(): BelongsTo
    {
        return $this->belongsTo(WarehouseLocationModel::class, 'from_location_id');
    }

    public function toLocation(): BelongsTo
    {
        return $this->belongsTo(WarehouseLocationModel::class, 'to_location_id');
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'requested_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'approved_by');
    }

    public function stockTransferLines(): HasMany
    {
        return $this->hasMany(StockTransferLineModel::class, 'stock_transfer_id');
    }
}

