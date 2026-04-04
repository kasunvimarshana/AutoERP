<?php
namespace Modules\Authorization\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;

class RoleModel extends BaseModel
{
    protected $table = 'roles';
    protected $guarded = ['id'];

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(PermissionModel::class, 'role_permissions', 'role_id', 'permission_id');
    }
}
