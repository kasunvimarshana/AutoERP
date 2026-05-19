<?php

namespace Modules\SystemUser\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

class SystemUserModel extends Model
{
    protected $table = 'system_users';
    protected $guarded = [];
}
