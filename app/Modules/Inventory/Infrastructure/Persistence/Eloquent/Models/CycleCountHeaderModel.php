<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Audit\Infrastructure\Persistence\Eloquent\Traits\HasAudit;
use Modules\OrganizationUnit\Infrastructure\Persistence\Eloquent\Models\OrganizationUnitModel;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Traits\HasTenant;
use Modules\Warehouse\Infrastructure\Persistence\Eloquent\Models\WarehouseLocationModel;
use Modules\Warehouse\Infrastructure\Persistence\Eloquent\Models\WarehouseModel;

class CycleCountHeaderModel extends Model
{
    use HasAudit;
    use HasTenant;

    protected $table = 'cycle_count_headers';

    protected $fillable = [
        'tenant_id',
        'org_unit_id',
        'row_version',
        'warehouse_id',
        'location_id',
        'status',
        'counted_by_user_id',
        'counted_at',
        'approved_by_user_id',
        'approved_at',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'org_unit_id' => 'integer',
        'row_version' => 'integer',
        'warehouse_id' => 'integer',
        'location_id' => 'integer',
        'counted_by_user_id' => 'integer',
        'approved_by_user_id' => 'integer',
        'counted_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    public function organizationUnit(): BelongsTo
    {
        return $this->belongsTo(OrganizationUnitModel::class, 'org_unit_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(WarehouseModel::class, 'warehouse_id');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(WarehouseLocationModel::class, 'location_id');
    }

    public function countedBy(): BelongsTo
    {
        return $this->belongsTo((string) config('auth.providers.users.model'), 'counted_by_user_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo((string) config('auth.providers.users.model'), 'approved_by_user_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(CycleCountLineModel::class, 'count_header_id');
    }
}
