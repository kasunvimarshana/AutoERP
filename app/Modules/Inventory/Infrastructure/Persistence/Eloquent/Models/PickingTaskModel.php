<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Infrastructure\Persistence\Eloquent\Concerns\HasOrganizationUnitScope;
use Modules\Core\Infrastructure\Persistence\Eloquent\Concerns\HasStatusScope;
use Modules\Core\Infrastructure\Persistence\Eloquent\Concerns\HasTenantScope;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\ReceiptInspectionModel;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\StockMovementModel;
use Modules\OrganizationUnit\Infrastructure\Persistence\Eloquent\Models\OrganizationUnitModel;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Models\TenantModel;
use Modules\User\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Modules\Warehouse\Infrastructure\Persistence\Eloquent\Models\WarehouseLocationModel;
use Modules\Warehouse\Infrastructure\Persistence\Eloquent\Models\WarehouseModel;

class PickingTaskModel extends Model
{
    use HasTenantScope, HasOrganizationUnitScope, HasStatusScope;

    protected $table = 'picking_tasks';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'completed_at' => 'datetime',
            'metadata' => 'array',
            'quantity' => 'decimal:4',
            'row_version' => 'integer',
        ];
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'assigned_user_id');
    }

    public function organizationUnit(): BelongsTo
    {
        return $this->belongsTo(OrganizationUnitModel::class, 'organization_unit_id');
    }

    public function receiptInspection(): BelongsTo
    {
        return $this->belongsTo(ReceiptInspectionModel::class, 'receipt_inspection_id');
    }

    public function sourceLocation(): BelongsTo
    {
        return $this->belongsTo(WarehouseLocationModel::class, 'source_location_id');
    }

    public function sourceWarehouse(): BelongsTo
    {
        return $this->belongsTo(WarehouseModel::class, 'source_warehouse_id');
    }

    public function stockMovement(): BelongsTo
    {
        return $this->belongsTo(StockMovementModel::class, 'stock_movement_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(TenantModel::class, 'tenant_id');
    }

}
