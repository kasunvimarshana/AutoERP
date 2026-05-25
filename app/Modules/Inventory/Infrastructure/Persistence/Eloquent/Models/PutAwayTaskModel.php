<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Concerns\HasOrganizationUnitScope;
use Modules\Core\Infrastructure\Persistence\Eloquent\Concerns\HasStatusScope;
use Modules\Core\Infrastructure\Persistence\Eloquent\Concerns\HasTenantScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\OrganizationUnit\Infrastructure\Persistence\Eloquent\Models\OrganizationUnitModel;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Models\TenantModel;
use Modules\User\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Modules\Warehouse\Infrastructure\Persistence\Eloquent\Models\WarehouseLocationModel;
use Modules\Warehouse\Infrastructure\Persistence\Eloquent\Models\WarehouseModel;

class PutAwayTaskModel extends Model
{
    use HasOrganizationUnitScope, HasStatusScope, HasTenantScope;

    protected $table = 'put_away_tasks';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'assigned_user_id' => 'integer',
            'completed_at' => 'datetime',
            'metadata' => 'array',
            'organization_unit_id' => 'integer',
            'quantity' => 'decimal:4',
            'receipt_inspection_id' => 'integer',
            'row_version' => 'integer',
            'stock_movement_id' => 'integer',
            'target_location_id' => 'integer',
            'target_warehouse_id' => 'integer',
            'tenant_id' => 'integer',
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

    public function receiptInspection(): BelongsTo
    {
        return $this->belongsTo(ReceiptInspectionModel::class, 'receipt_inspection_id');
    }

    public function stockMovement(): BelongsTo
    {
        return $this->belongsTo(StockMovementModel::class, 'stock_movement_id');
    }

    public function targetWarehouse(): BelongsTo
    {
        return $this->belongsTo(WarehouseModel::class, 'target_warehouse_id');
    }

    public function targetLocation(): BelongsTo
    {
        return $this->belongsTo(WarehouseLocationModel::class, 'target_location_id');
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'assigned_user_id');
    }
}

