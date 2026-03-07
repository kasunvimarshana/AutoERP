<?php

namespace App\Models;

use App\Scopes\TenantScope;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    protected $fillable = ['tenant_id', 'name', 'email', 'password', 'roles', 'attributes', 'is_active'];
    protected $hidden = ['password'];
    protected $casts = ['roles' => 'array', 'attributes' => 'array', 'is_active' => 'boolean'];

    protected static function booted(): void
    {
        static::addGlobalScope(new TenantScope());
    }

    public function hasRole(string $role): bool { return in_array($role, $this->roles ?? []); }
    public function hasAnyRole(array $roles): bool { return !empty(array_intersect($roles, $this->roles ?? [])); }
}
