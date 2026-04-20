<?php

declare(strict_types=1);

namespace Modules\User\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\Contracts\OAuthenticatable;
use Laravel\Passport\HasApiTokens;
use Modules\Audit\Infrastructure\Persistence\Eloquent\Traits\HasAudit;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Traits\HasTenant;

class UserModel extends Authenticatable implements OAuthenticatable
{
    use HasApiTokens, HasAudit, Notifiable, SoftDeletes;
    use HasTenant;

    protected $table = 'users';

    protected $fillable = [
        'tenant_id',
        'org_unit_id',
        'email',
        'password',
        'first_name',
        'last_name',
        'phone',
        'avatar',
        'email_verified_at',
        'remember_token',
        'status',
        'address',
        'preferences',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'address' => 'array',
        'preferences' => 'array',
    ];

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(RoleModel::class, 'role_user', 'user_id', 'role_id')
            ->withTimestamps();
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(UserAttachmentModel::class, 'user_id');
    }

    public function devices(): HasMany
    {
        return $this->hasMany(UserDeviceModel::class, 'user_id');
    }
}
