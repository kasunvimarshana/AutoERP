<?php

namespace Modules\User\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class UserModel extends Model
{
    use SoftDeletes;

    protected $table = 'users';
    protected $guarded = ['id'];
    protected $casts = [
        'address'     => 'array',
        'preferences' => 'array',
        'active'      => 'boolean',
    ];

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(RoleModel::class, 'role_user', 'user_id', 'role_id')
                    ->withTimestamps();
    }

    public function attachments()
    {
        return $this->hasMany(UserAttachmentModel::class, 'user_id');
    }
}
