<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Department Model - Lowest level of the hierarchical structure.
 * Represents specific functional units (e.g., Pharmacy, Logistics).
 */
class Department extends Model
{
    protected $fillable = ['location_id', 'name', 'slug', 'manager_id'];

    /**
     * Define relationship with location.
     */
    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * Define relationship with users.
     */
    public function users()
    {
        return $this->hasMany(User::class);
    }
}
