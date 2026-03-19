<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Organization Model - Secondary level of the hierarchical structure.
 * A single tenant can have multiple legal entities/organizations.
 */
class Organization extends Model
{
    protected $fillable = ['tenant_id', 'name', 'slug', 'country', 'currency'];

    /**
     * Define relationship with tenant.
     */
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Define relationship with branches.
     */
    public function branches()
    {
        return $this->hasMany(Branch::class);
    }
}
