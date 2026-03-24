<?php

namespace Modules\User\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class RoleModel extends Model
{
    protected $table = 'roles';
    protected $fillable = [
        'tenant_id',
        'name',
        'guard_name',
    ];

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(PermissionModel::class, 'permission_role', 'role_id', 'permission_id')
                    ->withTimestamps();
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(UserModel::class, 'role_user', 'role_id', 'user_id')
                    ->withTimestamps();
    }
}
