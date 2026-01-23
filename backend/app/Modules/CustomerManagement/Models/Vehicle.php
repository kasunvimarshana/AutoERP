<?php

namespace App\Modules\CustomerManagement\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Vehicle extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'tenant_id',
        'current_customer_id',
        'vin',
        'registration_number',
        'make',
        'model',
        'year',
        'color',
        'engine_number',
        'chassis_number',
        'vehicle_type',
        'fuel_type',
        'transmission',
        'engine_capacity',
        'current_mileage',
        'mileage_unit',
        'last_service_mileage',
        'next_service_mileage',
        'last_service_date',
        'next_service_date',
        'service_interval_days',
        'service_interval_mileage',
        'insurance_provider',
        'insurance_policy_number',
        'insurance_expiry_date',
        'registration_expiry_date',
        'specifications',
        'metadata',
        'notes',
        'status',
        'ownership_start_date',
        'total_services',
    ];

    protected $casts = [
        'current_mileage' => 'decimal:2',
        'last_service_mileage' => 'decimal:2',
        'next_service_mileage' => 'decimal:2',
        'last_service_date' => 'datetime',
        'next_service_date' => 'datetime',
        'insurance_expiry_date' => 'date',
        'registration_expiry_date' => 'date',
        'ownership_start_date' => 'datetime',
        'specifications' => 'array',
        'metadata' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($vehicle) {
            if (empty($vehicle->uuid)) {
                $vehicle->uuid = Str::uuid();
            }
        });
    }

    /**
     * Get the current owner of the vehicle
     */
    public function currentCustomer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'current_customer_id');
    }

    /**
     * Get the tenant that owns the vehicle
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Tenant::class);
    }

    /**
     * Get ownership history
     */
    public function ownershipHistory(): HasMany
    {
        return $this->hasMany(VehicleOwnershipHistory::class);
    }

    /**
     * Get full vehicle name
     */
    public function getFullNameAttribute(): string
    {
        return "{$this->year} {$this->make} {$this->model}";
    }

    /**
     * Check if service is due
     */
    public function isServiceDue(): bool
    {
        if ($this->next_service_date && now()->gte($this->next_service_date)) {
            return true;
        }

        if ($this->next_service_mileage && $this->current_mileage >= $this->next_service_mileage) {
            return true;
        }

        return false;
    }

    /**
     * Calculate next service mileage
     */
    public function calculateNextServiceMileage(): float
    {
        return $this->current_mileage + $this->service_interval_mileage;
    }

    /**
     * Activity log options
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['*'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Scope: Active vehicles
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope: By tenant
     */
    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope: Service due
     */
    public function scopeServiceDue($query)
    {
        return $query->where(function ($q) {
            $q->where('next_service_date', '<=', now())
                ->orWhereRaw('current_mileage >= next_service_mileage');
        });
    }
}
