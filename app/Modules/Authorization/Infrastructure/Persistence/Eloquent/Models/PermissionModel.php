<?php
namespace Modules\Authorization\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;

class PermissionModel extends BaseModel
{
    protected $table = 'permissions';
    protected $guarded = ['id'];

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(RoleModel::class, 'role_permissions', 'permission_id', 'role_id');
    }
}
