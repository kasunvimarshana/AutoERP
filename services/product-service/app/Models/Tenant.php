<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Lightweight Tenant stub used for middleware tenant resolution.
 * In a production multi-service setup this would share the same DB or call
 * the User Service via HTTP. Here it mirrors enough schema to resolve tenants
 * from the local (shared) tenants table or via the X-Tenant-ID header.
 */
class Tenant extends Model
{
    protected $fillable = [
        'name',
        'domain',
        'is_active',
        'settings',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'settings'  => 'array',
    ];
}
