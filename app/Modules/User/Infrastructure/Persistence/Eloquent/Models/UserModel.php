<?php
namespace Modules\User\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasAudit;

class UserModel extends Authenticatable
{
    use HasAudit, SoftDeletes, Notifiable;

    protected $table = 'users';
    protected $guarded = ['id'];
    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'preferences'       => 'array',
        'created_at'        => 'datetime',
        'updated_at'        => 'datetime',
        'deleted_at'        => 'datetime',
    ];
}
