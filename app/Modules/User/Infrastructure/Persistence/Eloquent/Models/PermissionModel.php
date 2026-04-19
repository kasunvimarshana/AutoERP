<?php

declare(strict_types=1);

namespace Modules\User\Infrastructure\Persistence\Eloquent\Models;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Traits\HasTenant;

use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Modules\Audit\Infrastructure\Persistence\Eloquent\Traits\HasAudit;

class PermissionModel extends BaseModel
{

    use HasTenant;
    use HasAudit;
    protected $table = 'permissions';

    protected $fillable = [
        'tenant_id',
        'name',
        'guard_name',
        'module',
        'description',
        'guard_name',
    ];

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(RoleModel::class, 'permission_role', 'permission_id', 'role_id')
            ->withTimestamps();
    }
}
