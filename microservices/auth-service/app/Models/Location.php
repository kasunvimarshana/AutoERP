<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Location Model - Quaternary level of the hierarchical structure.
 * Represents a specific warehouse or sub-office.
 */
class Location extends Model
{
    protected $fillable = ['branch_id', 'name', 'slug', 'type'];

    /**
     * Define relationship with branch.
     */
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Define relationship with departments.
     */
    public function departments()
    {
        return $this->hasMany(Department::class);
    }
}
