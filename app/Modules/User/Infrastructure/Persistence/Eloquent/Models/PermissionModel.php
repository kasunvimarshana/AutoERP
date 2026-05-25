<?php

declare(strict_types=1);

namespace Modules\User\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Infrastructure\Persistence\Eloquent\Concerns\HasOrganizationUnitScope;
use Modules\Core\Infrastructure\Persistence\Eloquent\Concerns\HasTenantScope;
use Modules\OrganizationUnit\Infrastructure\Persistence\Eloquent\Models\OrganizationUnitModel;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Models\TenantModel;
use Modules\User\Infrastructure\Persistence\Eloquent\Models\RolePermissionModel;
use Modules\User\Infrastructure\Persistence\Eloquent\Models\UserPermissionModel;

class PermissionModel extends Model
{
    use HasTenantScope, HasOrganizationUnitScope, SoftDeletes;

    protected $table = 'permissions';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'row_version' => 'integer',
        ];
    }

    public function organizationUnit(): BelongsTo
    {
        return $this->belongsTo(OrganizationUnitModel::class, 'organization_unit_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(TenantModel::class, 'tenant_id');
    }

    public function rolePermissions(): HasMany
    {
        return $this->hasMany(RolePermissionModel::class, 'permission_id');
    }

    public function userPermissions(): HasMany
    {
        return $this->hasMany(UserPermissionModel::class, 'permission_id');
    }

}
