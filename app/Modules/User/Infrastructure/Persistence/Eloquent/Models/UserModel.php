<?php

namespace Modules\User\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

class UserModel extends Model
{
    protected $table = 'users';
    protected $guarded = [];

    protected $casts = [
        'preferences' => 'array'
    ];

    public function roles()
    {
        return $this->belongsToMany(RoleModel::class, 'user_roles', 'user_id', 'role_id');
    }

    public function attachments()
    {
        return $this->hasMany(UserDocumentModel::class, 'user_id');
    }

    public function devices()
    {
        return $this->hasMany(UserDeviceModel::class, 'user_id');
    }
}
