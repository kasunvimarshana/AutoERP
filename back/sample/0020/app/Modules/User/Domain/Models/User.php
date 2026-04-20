<?php

namespace App\Modules\User\Domain\Models;

use App\Modules\Core\Domain\Scopes\TenantScope;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable {
    use HasApiTokens, SoftDeletes;

    protected $fillable = ['tenant_id', 'name', 'email', 'password', 'user_type'];
    protected $hidden = ['password', 'remember_token'];

    protected static function booted() {
        static::addGlobalScope(new TenantScope());
    }
}
