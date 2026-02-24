<?php
namespace Modules\User\Infrastructure\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Modules\Shared\Infrastructure\Traits\HasTenantScope;
class RoleModel extends Model
{
    use HasUuids, HasTenantScope;
    protected $table = 'roles';
    protected $fillable = ['id', 'tenant_id', 'name', 'guard_name', 'description'];
    public function permissions(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(PermissionModel::class, 'permission_role', 'role_id', 'permission_id');
    }
}
