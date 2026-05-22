<?php

declare(strict_types=1);

namespace Modules\User\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\User\Infrastructure\Persistence\Eloquent\Concerns\HasTenantAndOrganizationScopes;

class User extends Model
{
    use HasTenantAndOrganizationScopes;
    use SoftDeletes;

    protected $table = 'users';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'row_version' => 'integer',
            'metadata' => 'array',
            'preferences' => 'array',
            'email_verified_at' => 'datetime',
            'date_of_birth' => 'date',
        ];
    }

    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('status', 'active');
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

    public function userRoles(): HasMany
    {
        return $this->hasMany(
            'Modules\\User\\Infrastructure\\Persistence\\Eloquent\\Models\\UserRole',
            'user_id'
        );
    }

    public function userPermissions(): HasMany
    {
        return $this->hasMany(
            'Modules\\User\\Infrastructure\\Persistence\\Eloquent\\Models\\UserPermission',
            'user_id'
        );
    }

    public function userTenants(): HasMany
    {
        return $this->hasMany(
            'Modules\\User\\Infrastructure\\Persistence\\Eloquent\\Models\\UserTenant',
            'user_id'
        );
    }

    public function documents(): HasMany
    {
        return $this->hasMany(
            'Modules\\User\\Infrastructure\\Persistence\\Eloquent\\Models\\UserDocument',
            'user_id'
        );
    }

    public function devices(): HasMany
    {
        return $this->hasMany(
            'Modules\\User\\Infrastructure\\Persistence\\Eloquent\\Models\\UserDevice',
            'user_id'
        );
    }
}
