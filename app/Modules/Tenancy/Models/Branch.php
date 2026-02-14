<?php

namespace App\Modules\Tenancy\Models;

use App\Core\Traits\TenantScoped;
use App\Models\User;
use App\Modules\Inventory\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Branch Model
 * 
 * Represents a branch of an organization within a tenant
 */
class Branch extends Model
{
    use HasFactory, TenantScoped;

    protected $fillable = [
        'organization_id',
        'name',
        'code',
        'address',
        'latitude',
        'longitude',
        'is_active',
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $hidden = [];

    /**
     * Get the tenant that owns this branch
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the organization that owns this branch
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Get users assigned to this branch
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Get warehouses for this branch
     */
    public function warehouses(): HasMany
    {
        return $this->hasMany(Warehouse::class);
    }
}
