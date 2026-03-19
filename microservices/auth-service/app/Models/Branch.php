<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Branch Model - Tertiary level of the hierarchical structure.
 * Represents a physical or logical store/office.
 */
class Branch extends Model
{
    protected $fillable = ['organization_id', 'name', 'slug', 'address'];

    /**
     * Define relationship with organization.
     */
    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Define relationship with locations.
     */
    public function locations()
    {
        return $this->hasMany(Location::class);
    }
}
