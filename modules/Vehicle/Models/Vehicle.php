<?php

namespace Modules\Vehicle\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Customer\Models\Customer;

/**
 * Vehicle Model
 *
 * Represents a vehicle in the system.
 * Supports multi-tenancy, ownership tracking, and service history.
 *
 * @property int $id
 * @property int $tenant_id
 * @property int $customer_id
 * @property string $vin
 * @property string $registration_number
 * @property string $make
 * @property string $model
 * @property int $year
 * @property string|null $color
 * @property string|null $engine_number
 * @property int|null $current_mileage
 * @property string|null $fuel_type
 * @property string|null $transmission_type
 * @property array|null $specifications
 * @property \Illuminate\Support\Carbon|null $purchase_date
 * @property \Illuminate\Support\Carbon|null $next_service_date
 * @property int|null $next_service_mileage
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
class Vehicle extends Model
{
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tenant_id',
        'customer_id',
        'vin',
        'registration_number',
        'make',
        'model',
        'year',
        'color',
        'engine_number',
        'current_mileage',
        'fuel_type',
        'transmission_type',
        'specifications',
        'purchase_date',
        'next_service_date',
        'next_service_mileage',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'specifications' => 'array',
        'purchase_date' => 'date',
        'next_service_date' => 'date',
        'current_mileage' => 'integer',
        'next_service_mileage' => 'integer',
        'year' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the customer that owns the vehicle.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the service history for this vehicle.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\Modules\Customer\Models\Customer, $this>
     */
    public function serviceHistory(): HasMany
    {
        // Placeholder - will be implemented when ServiceHistory module is completed
        // Using Customer as placeholder to satisfy type requirements
        return $this->hasMany(\Modules\Customer\Models\Customer::class, 'id', 'id')->where('id', -1);
    }

    /**
     * Get the meter readings for this vehicle.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\Modules\Customer\Models\Customer, $this>
     */
    public function meterReadings(): HasMany
    {
        // Placeholder - will be implemented when MeterReading module is completed
        // Using Customer as placeholder to satisfy type requirements
        return $this->hasMany(\Modules\Customer\Models\Customer::class, 'id', 'id')->where('id', -1);
    }

    /**
     * Get the job cards for this vehicle.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\Modules\Customer\Models\Customer, $this>
     */
    public function jobCards(): HasMany
    {
        // Placeholder - will be implemented when JobCard module is completed
        // Using Customer as placeholder to satisfy type requirements
        return $this->hasMany(\Modules\Customer\Models\Customer::class, 'id', 'id')->where('id', -1);
    }

    /**
     * Get the ownership history for this vehicle.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\Modules\Customer\Models\Customer, $this>
     */
    public function ownershipHistory(): HasMany
    {
        // Placeholder - will be implemented when VehicleOwnership module is completed
        // Using Customer as placeholder to satisfy type requirements
        return $this->hasMany(\Modules\Customer\Models\Customer::class, 'id', 'id')->where('id', -1);
    }

    /**
     * Scope a query to only include vehicles of a specific tenant.
     */
    public function scopeTenant($query, $tenantId = null)
    {
        $tenantId = $tenantId ?? $this->getCurrentTenantId();

        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope a query to vehicles needing service.
     */
    public function scopeNeedingService($query)
    {
        return $query->where(function ($q) {
            $q->where('next_service_date', '<=', now())
                ->orWhereRaw('current_mileage >= next_service_mileage');
        });
    }

    /**
     * Get display name for the vehicle.
     */
    public function getDisplayNameAttribute(): string
    {
        return "{$this->year} {$this->make} {$this->model}";
    }

    /**
     * Check if vehicle needs service.
     */
    public function needsService(): bool
    {
        if ($this->next_service_date && $this->next_service_date <= now()) {
            return true;
        }

        if ($this->next_service_mileage && $this->current_mileage >= $this->next_service_mileage) {
            return true;
        }

        return false;
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
