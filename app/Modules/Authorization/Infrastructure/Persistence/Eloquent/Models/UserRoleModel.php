<?php
declare(strict_types=1);
namespace Modules\Authorization\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

class UserRoleModel extends Model
{
    protected $table = 'user_roles';
    public $timestamps = false;
    protected $fillable = ['user_id', 'role_id'];
}
