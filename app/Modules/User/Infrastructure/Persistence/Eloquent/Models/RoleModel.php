<?php

declare(strict_types=1);

namespace Modules\User\Infrastructure\Persistence\Eloquent\Models;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Traits\HasTenant;

use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Modules\Audit\Infrastructure\Persistence\Eloquent\Traits\HasAudit;

class RoleModel extends BaseModel
{

    use HasTenant;
    use HasAudit;
    protected $table = 'roles';

    protected $fillable = [
        'tenant_id',
        'name',
        'guard_name',
        'description',
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
