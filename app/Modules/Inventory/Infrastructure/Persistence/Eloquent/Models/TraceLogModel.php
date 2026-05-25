<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Modules\Core\Infrastructure\Persistence\Eloquent\Concerns\HasOrganizationUnitScope;
use Modules\Core\Infrastructure\Persistence\Eloquent\Concerns\HasTenantScope;
use Modules\Item\Infrastructure\Persistence\Eloquent\Models\ItemIdentifierModel;
use Modules\OrganizationUnit\Infrastructure\Persistence\Eloquent\Models\OrganizationUnitModel;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Models\TenantModel;
use Modules\User\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Modules\Warehouse\Infrastructure\Persistence\Eloquent\Models\WarehouseLocationModel;
use Modules\Warehouse\Infrastructure\Persistence\Eloquent\Models\WarehouseModel;

class TraceLogModel extends Model
{
    use HasTenantScope, HasOrganizationUnitScope;

    protected $table = 'trace_logs';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'performed_at' => 'datetime',
            'quantity' => 'decimal:4',
            'row_version' => 'integer',
        ];
    }

    public function destinationLocation(): BelongsTo
    {
        return $this->belongsTo(WarehouseLocationModel::class, 'destination_location_id');
    }

    public function destinationWarehouse(): BelongsTo
    {
        return $this->belongsTo(WarehouseModel::class, 'destination_warehouse_id');
    }

    public function identifier(): BelongsTo
    {
        return $this->belongsTo(ItemIdentifierModel::class, 'identifier_id');
    }

    public function organizationUnit(): BelongsTo
    {
        return $this->belongsTo(OrganizationUnitModel::class, 'organization_unit_id');
    }

    public function performedBy(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'performed_by');
    }

    public function sourceLocation(): BelongsTo
    {
        return $this->belongsTo(WarehouseLocationModel::class, 'source_location_id');
    }

    public function sourceWarehouse(): BelongsTo
    {
        return $this->belongsTo(WarehouseModel::class, 'source_warehouse_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(TenantModel::class, 'tenant_id');
    }

    public function entity(): MorphTo
    {
        return $this->morphTo();
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

}
