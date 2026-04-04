<?php
namespace Modules\Authorization\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

class UserRoleModel extends Model
{
    protected $table = 'user_roles';
    protected $guarded = ['id'];
    public $timestamps = false;
}
