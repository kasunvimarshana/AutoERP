<?php

namespace App\Modules\Tenancy\Models;

use App\Core\Traits\TenantScoped;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Organization Model
 * 
 * Represents an organization within a tenant
 */
class Organization extends Model
{
    use HasFactory, TenantScoped;

    protected $fillable = [
        'name',
        'registration_number',
        'tax_number',
        'settings',
    ];

    protected $casts = [
        'settings' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $hidden = [];

    /**
     * Get the tenant that owns this organization
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get branches for this organization
     */
    public function branches(): HasMany
    {
        return $this->hasMany(Branch::class);
    }

    /**
     * Get users for this organization
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
