<?php

declare(strict_types=1);

namespace Modules\User\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\User\Infrastructure\Persistence\Eloquent\Concerns\HasTenantAndOrganizationScopes as HasTenantAndOrganizationScopesTrait;

class Permission extends Model
{
    use HasTenantAndOrganizationScopesTrait;
    use SoftDeletes;

    protected $table = 'permissions';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'row_version' => 'integer',
            'metadata' => 'array',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(
            'Modules\\Tenant\\Infrastructure\\Persistence\\Eloquent\\Models\\Tenant',
            'tenant_id'
        );
    }

    public function organizationUnit(): BelongsTo
    {
        return $this->belongsTo(
            'Modules\\OrganizationUnit\\Infrastructure\\Persistence\\Eloquent\\Models\\OrganizationUnit',
            'organization_unit_id'
        );
    }

    public function rolePermissions(): HasMany
    {
        return $this->hasMany(
            'Modules\\User\\Infrastructure\\Persistence\\Eloquent\\Models\\RolePermission',
            'permission_id'
        );
    }

    public function userPermissions(): HasMany
    {
        return $this->hasMany(
            'Modules\\User\\Infrastructure\\Persistence\\Eloquent\\Models\\UserPermission',
            'permission_id'
        );
    }
}
