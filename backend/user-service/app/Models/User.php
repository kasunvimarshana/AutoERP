<?php

namespace App\Models;

use App\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'name',
        'email',
        'password',
        'roles',
        'attributes',
        'is_active',
        'auth_user_id',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'roles' => 'array',
        'attributes' => 'array',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new TenantScope());

        static::creating(function (self $model) {
            if (app()->has('current_tenant_id') && empty($model->tenant_id)) {
                $model->tenant_id = app('current_tenant_id');
            }
        });
    }

    public function hasRole(string $role): bool
    {
        return in_array($role, $this->roles ?? []);
    }

    public function hasAnyRole(array $roles): bool
    {
        return !empty(array_intersect($roles, $this->roles ?? []));
    }

    public function getJsonAttribute(string $key): mixed
    {
        return ($this->attributes ?? [])[$key] ?? null;
    }
}
