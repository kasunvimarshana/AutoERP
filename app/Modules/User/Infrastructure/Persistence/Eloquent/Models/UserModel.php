<?php
declare(strict_types=1);
namespace Modules\User\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;

class UserModel extends BaseModel implements AuthenticatableContract
{
    use Authenticatable, HasApiTokens, Notifiable;

    protected $table = 'users';
    protected $fillable = [
        'tenant_id', 'name', 'email', 'password', 'status',
        'phone', 'avatar', 'preferences', 'email_verified_at',
    ];
    protected $hidden = ['password', 'remember_token'];
    protected $casts = [
        'id' => 'int',
        'tenant_id' => 'int',
        'preferences' => 'array',
        'email_verified_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];
}
