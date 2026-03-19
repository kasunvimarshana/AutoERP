<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Permission Model - Granular permissions for RBAC/ABAC.
 */
class Permission extends Model
{
    protected $fillable = ['name', 'slug', 'description'];

    /**
     * Define relationship with roles.
     */
    public function roles()
    {
        return $this->belongsToMany(Role::class);
    }
}
