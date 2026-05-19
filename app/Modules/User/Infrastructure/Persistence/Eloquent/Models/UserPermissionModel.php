<?php

namespace Modules\User\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

class UserPermissionModel extends Model
{
    protected $table = 'user_permissions';
    protected $guarded = [];
}
