<?php

namespace App\Modules\Tenancy\Models;

use App\Core\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Tenant Model
 * 
 * Represents a tenant in the multi-tenant system
 */
class Tenant extends Model
{
    use HasFactory, HasUuid, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'domain',
        'database',
        'status',
        'trial_ends_at',
    ];

    protected $casts = [
        'trial_ends_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $hidden = [];

    protected static function newFactory()
    {
        return \Database\Factories\TenantFactory::new();
    }

    /**
     * Get subscriptions for this tenant
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    /**
     * Get organizations for this tenant
     */
    public function organizations(): HasMany
    {
        return $this->hasMany(Organization::class);
    }

    /**
     * Get branches for this tenant
     */
    public function branches(): HasMany
    {
        return $this->hasMany(Branch::class);
    }
}
