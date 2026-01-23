<?php

namespace Modules\Customer\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Customer Model
 *
 * Represents a customer in the system.
 * Supports multi-tenancy and vehicle ownership.
 *
 * @property int $id
 * @property int $tenant_id
 * @property string $first_name
 * @property string $last_name
 * @property string $email
 * @property string $phone
 * @property string|null $address
 * @property string|null $city
 * @property string|null $state
 * @property string|null $zip_code
 * @property string|null $country
 * @property array|null $preferences
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
class Customer extends Model
{
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tenant_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'address',
        'city',
        'state',
        'zip_code',
        'country',
        'preferences',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'preferences' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the vehicles owned by this customer.
     */
    public function vehicles(): HasMany
    {
        // Placeholder - will be implemented when Vehicle module is completed
        return $this->hasMany(\Modules\Vehicle\Models\Vehicle::class);
    }

    /**
     * Get the appointments for this customer.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\Modules\Vehicle\Models\Vehicle, $this>
     */
    public function appointments(): HasMany
    {
        // Placeholder - will be implemented when Appointment module is completed
        // Using Vehicle as placeholder to satisfy type requirements
        return $this->hasMany(\Modules\Vehicle\Models\Vehicle::class, 'id', 'id')->where('id', -1);
    }

    /**
     * Get the job cards for this customer.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\Modules\Vehicle\Models\Vehicle, $this>
     */
    public function jobCards(): HasMany
    {
        // Placeholder - will be implemented when JobCard module is completed
        // Using Vehicle as placeholder to satisfy type requirements
        return $this->hasMany(\Modules\Vehicle\Models\Vehicle::class, 'id', 'id')->where('id', -1);
    }

    /**
     * Get the invoices for this customer.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\Modules\Vehicle\Models\Vehicle, $this>
     */
    public function invoices(): HasMany
    {
        // Placeholder - will be implemented when Invoice module is completed
        // Using Vehicle as placeholder to satisfy type requirements
        return $this->hasMany(\Modules\Vehicle\Models\Vehicle::class, 'id', 'id')->where('id', -1);
    }

    /**
     * Scope a query to only include customers of a specific tenant.
     */
    public function scopeTenant($query, $tenantId = null)
    {
        $tenantId = $tenantId ?? $this->getCurrentTenantId();

        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Get the current tenant ID.
     */
    protected function getCurrentTenantId(): ?int
    {
        // return auth()->user()?->tenant_id;
        return null;
    }

    /**
     * Get full name of the customer.
     */
    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        // Auto-set tenant_id on creation
        static::creating(function ($model) {
            if (empty($model->tenant_id)) {
                // $model->tenant_id = auth()->user()?->tenant_id ?? 1;
                $model->tenant_id = 1; // Default tenant for now
            }
        });

        // Global scope for tenant isolation
        // static::addGlobalScope('tenant', function ($query) {
        //     if ($tenantId = auth()->user()?->tenant_id) {
        //         $query->where('tenant_id', $tenantId);
        //     }
        // });
    }
}
