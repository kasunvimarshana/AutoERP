<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Role Model - Supports Role-Based Access Control (RBAC).
 */
class Role extends Model
{
    protected $fillable = ['name', 'slug', 'description', 'tenant_id'];

    /**
     * Define relationship with permissions.
     */
    public function permissions()
    {
        return $this->belongsToMany(Permission::class);
    }

    /**
     * Define relationship with users.
     */
    public function users()
    {
        return $this->hasMany(User::class);
    }
}
