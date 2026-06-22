<?php

declare(strict_types=1);

namespace Modules\User\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Modules\Core\Models\Concerns\HasStatusScope;
use Modules\OrganizationUnit\Models\OrganizationUnitModel;
use Modules\Tenant\Models\TenantModel;

final class UserModel extends Authenticatable
{
    use HasStatusScope;
    use Notifiable;
    use SoftDeletes;

    protected $table = 'users';

    protected $guarded = ['id', 'is_platform_operator'];

    protected $hidden = ['password', 'remember_token', 'is_platform_operator'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'metadata' => 'array',
            'preferences' => 'array',
            'date_of_birth' => 'date',
            'email_verified_at' => 'datetime',
            'is_platform_operator' => 'boolean',
            'row_version' => 'integer',
        ]);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(TenantModel::class, 'tenant_id');
    }

    public function organizationUnit(): BelongsTo
    {
        return $this->belongsTo(OrganizationUnitModel::class, 'organization_unit_id');
    }

    public function roles(): HasMany
    {
        return $this->hasMany(UserRoleModel::class, 'user_id');
    }

    public function permissions(): HasMany
    {
        return $this->hasMany(UserPermissionModel::class, 'user_id');
    }

    public function tenants(): HasMany
    {
        return $this->hasMany(UserTenantModel::class, 'user_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(UserDocumentModel::class, 'user_id');
    }

    public function devices(): HasMany
    {
        return $this->hasMany(UserDeviceModel::class, 'user_id');
    }
}
