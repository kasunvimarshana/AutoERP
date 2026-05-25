<?php

declare(strict_types=1);

namespace Modules\User\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Infrastructure\Persistence\Eloquent\Concerns\HasStatusScope;
// use Modules\Core\Infrastructure\Persistence\Eloquent\Models\CoreModel;
use Modules\OrganizationUnit\Infrastructure\Persistence\Eloquent\Models\OrganizationUnitModel;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Models\TenantModel;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\Contracts\OAuthenticatable;
use Laravel\Passport\HasApiTokens;


final class UserModel extends Authenticatable implements OAuthenticatable
{
    use HasStatusScope;
    use SoftDeletes, HasApiTokens, Notifiable;

    protected $table = 'users';

    protected $guarded = ['id'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'preferences' => 'array',
            'date_of_birth' => 'date',
            'email_verified_at' => 'datetime',
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
