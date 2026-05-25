<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Infrastructure\Persistence\Eloquent\Concerns\HasOrganizationUnitScope;
use Modules\Core\Infrastructure\Persistence\Eloquent\Concerns\HasStatusScope;
use Modules\Core\Infrastructure\Persistence\Eloquent\Concerns\HasTenantScope;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\TransferOrderLineModel;
use Modules\OrganizationUnit\Infrastructure\Persistence\Eloquent\Models\OrganizationUnitModel;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Models\TenantModel;
use Modules\Warehouse\Infrastructure\Persistence\Eloquent\Models\WarehouseModel;

class TransferOrderModel extends Model
{
    use HasTenantScope, HasOrganizationUnitScope, HasStatusScope;

    protected $table = 'transfer_orders';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'expected_date' => 'date',
            'metadata' => 'array',
            'received_date' => 'date',
            'request_date' => 'date',
            'row_version' => 'integer',
            'shipped_date' => 'date',
        ];
    }

    public function fromWarehouse(): BelongsTo
    {
        return $this->belongsTo(WarehouseModel::class, 'from_warehouse_id');
    }

    public function organizationUnit(): BelongsTo
    {
        return $this->belongsTo(OrganizationUnitModel::class, 'organization_unit_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(TenantModel::class, 'tenant_id');
    }

    public function toWarehouse(): BelongsTo
    {
        return $this->belongsTo(WarehouseModel::class, 'to_warehouse_id');
    }

    public function transferOrderLines(): HasMany
    {
        return $this->hasMany(TransferOrderLineModel::class, 'transfer_order_id');
    }

}
