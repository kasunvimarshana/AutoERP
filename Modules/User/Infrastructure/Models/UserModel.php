<?php
namespace Modules\User\Infrastructure\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Modules\Shared\Infrastructure\Traits\HasTenantScope;
class UserModel extends Authenticatable
{
    use HasApiTokens, HasFactory, HasUuids, HasTenantScope, Notifiable, SoftDeletes;
    protected $table = 'users';
    protected $fillable = [
        'id', 'tenant_id', 'name', 'email', 'password',
        'status', 'avatar_path', 'invited_by',
        'email_verified_at',
    ];
    protected $hidden = ['password', 'remember_token'];
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
    public function roles(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(RoleModel::class, 'role_user', 'user_id', 'role_id');
    }
}
