<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Tenant Model - Root level of the hierarchical multi-tenant structure.
 * Each tenant represents a single enterprise customer.
 */
class Tenant extends Model
{
    protected $fillable = ['name', 'slug', 'status', 'metadata'];

    protected $casts = [
        'metadata' => 'array',
        'status' => 'string',
    ];

    /**
     * Define relationship with organizations.
     */
    public function organizations()
    {
        return $this->hasMany(Organization::class);
    }
}
