<?php

declare(strict_types=1);

namespace Modules\Warehouse\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Audit\Infrastructure\Persistence\Eloquent\Traits\HasAudit;
use Modules\OrganizationUnit\Infrastructure\Persistence\Eloquent\Models\OrganizationUnitModel;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Traits\HasTenant;

class WarehouseModel extends Model
{
    use HasAudit;
    use HasTenant;

    protected $table = 'warehouses';

    protected $fillable = [
        'tenant_id',
        'org_unit_id',
        'row_version',
        'name',
        'code',
        'image_path',
        'type',
        'address_id',
        'is_active',
        'is_default',
        'metadata',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'org_unit_id' => 'integer',
        'row_version' => 'integer',
        'address_id' => 'integer',
        'type' => 'string',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function organizationUnit(): BelongsTo
    {
        return $this->belongsTo(OrganizationUnitModel::class, 'org_unit_id');
    }

    public function locations(): HasMany
    {
        return $this->hasMany(WarehouseLocationModel::class, 'warehouse_id');
    }
}
