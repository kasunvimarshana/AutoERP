<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrganizationUnitTypeModel extends Model
{
    protected $table = 'org_unit_types';

    protected $fillable = [
        'tenant_id',
        'name',
        'level',
        'is_active',
    ];

    protected $casts = [
        'level' => 'integer',
        'is_active' => 'boolean',
    ];

    public function organizationUnits(): HasMany
    {
        return $this->hasMany(OrganizationUnitModel::class, 'type_id');
    }
}
