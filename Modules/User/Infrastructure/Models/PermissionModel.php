<?php
namespace Modules\User\Infrastructure\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
class PermissionModel extends Model
{
    use HasUuids;
    protected $table = 'permissions';
    protected $fillable = ['id', 'name', 'guard_name', 'module', 'action', 'description'];
}
