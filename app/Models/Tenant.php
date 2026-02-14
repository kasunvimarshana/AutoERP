<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tenant extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'domain',
        'database',
        'settings',
        'is_active',
        'subscription_ends_at',
    ];

    protected $casts = [
        'settings' => 'array',
        'is_active' => 'boolean',
        'subscription_ends_at' => 'datetime',
    ];

    /**
     * Relationship: Tenant has many Users
     */
    public function users()
    {
        return $this->hasMany(User::class);
    }

    /**
     * Relationship: Tenant has many Vendors
     */
    public function vendors()
    {
        return $this->hasMany(Vendor::class);
    }

    /**
     * Relationship: Tenant has many Branches
     */
    public function branches()
    {
        return $this->hasMany(Branch::class);
    }
}
