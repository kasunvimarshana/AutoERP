<?php
declare(strict_types=1);
namespace Modules\Authorization\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;

class RoleModel extends BaseModel
{
    protected $table = 'roles';
    protected $fillable = ['tenant_id', 'name', 'slug', 'description'];
    protected $casts = [
        'id' => 'int',
        'tenant_id' => 'int',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function permissions()
    {
        return $this->belongsToMany(
            PermissionModel::class,
            'role_permissions',
            'role_id',
            'permission_id'
        );
    }
}
