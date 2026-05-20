<?php

namespace Modules\SystemUser\Infrastructure\Persistence\Eloquent\Models;

use Modules\User\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Illuminate\Database\Eloquent\Model;

class SystemUserModel extends Model
{
    protected $table = 'system_users';
    protected $guarded = [];

    public function user()
    {
        return $this->belongsTo(UserModel::class, 'user_id', 'id');
    }
}
