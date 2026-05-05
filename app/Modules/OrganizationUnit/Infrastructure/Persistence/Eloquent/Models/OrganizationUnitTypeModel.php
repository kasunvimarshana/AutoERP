<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Audit\Infrastructure\Persistence\Eloquent\Traits\HasAudit;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Traits\HasTenant;

class OrganizationUnitTypeModel extends BaseModel
{
    use HasAudit;
    use HasTenant;

    protected $table = 'org_unit_types';

    protected $fillable = [
        'tenant_id',
        'name',
        'level',
        'is_active',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'level' => 'integer',
        'is_active' => 'boolean',
    ];

    public function organizationUnits(): HasMany
    {
        return $this->hasMany(OrganizationUnitModel::class, 'type_id');
    }
}
