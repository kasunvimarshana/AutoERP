<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Infrastructure\Persistence\Eloquent\Concerns\HasOrganizationUnitScope;
use Modules\Core\Infrastructure\Persistence\Eloquent\Concerns\HasStatusScope;
use Modules\Core\Infrastructure\Persistence\Eloquent\Concerns\HasTenantScope;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\StockAdjustmentLineModel;
use Modules\OrganizationUnit\Infrastructure\Persistence\Eloquent\Models\OrganizationUnitModel;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Models\TenantModel;
use Modules\User\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Modules\Warehouse\Infrastructure\Persistence\Eloquent\Models\WarehouseLocationModel;
use Modules\Warehouse\Infrastructure\Persistence\Eloquent\Models\WarehouseModel;

class StockAdjustmentModel extends Model
{
    use HasTenantScope, HasOrganizationUnitScope, HasStatusScope;

    protected $table = 'stock_adjustments';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'approved_at' => 'datetime',
            'counted_at' => 'datetime',
            'metadata' => 'array',
            'row_version' => 'integer',
        ];
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'approved_by');
    }

    public function countedBy(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'counted_by');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(WarehouseLocationModel::class, 'location_id');
    }

    public function organizationUnit(): BelongsTo
    {
        return $this->belongsTo(OrganizationUnitModel::class, 'organization_unit_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(TenantModel::class, 'tenant_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(WarehouseModel::class, 'warehouse_id');
    }

    public function stockAdjustmentLines(): HasMany
    {
        return $this->hasMany(StockAdjustmentLineModel::class, 'stock_adjustment_id');
    }

}
