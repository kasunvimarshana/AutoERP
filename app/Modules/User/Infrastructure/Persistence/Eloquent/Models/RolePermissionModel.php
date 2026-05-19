<?php

namespace Modules\User\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

class RolePermissionModel extends Model
{
    protected $table = 'role_permissions';
    protected $guarded = [];
}
