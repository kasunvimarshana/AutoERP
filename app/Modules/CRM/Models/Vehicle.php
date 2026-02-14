<?php

namespace App\Modules\CRM\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Vehicle Model
 *
 * Manages vehicle information with centralized cross-branch service history.
 * Supports comprehensive vehicle tracking and maintenance records.
 */
class Vehicle extends Model
{
    use SoftDeletes;

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
        'odometer_reading',
        'fuel_type',
        'transmission',
        'purchase_date',
        'warranty_expiry',
        'metadata',
        'status',
    ];

    protected $casts = [
        'year' => 'integer',
        'odometer_reading' => 'integer',
        'purchase_date' => 'date',
        'warranty_expiry' => 'date',
        'metadata' => 'array',
    ];

    /**
     * Get the tenant that owns the vehicle.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Tenant\Models\Tenant::class);
    }

    /**
     * Get the customer that owns the vehicle.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get all service history records (cross-branch).
     */
    public function serviceHistory(): HasMany
    {
        return $this->hasMany(VehicleServiceHistory::class)->orderBy('service_date', 'desc');
    }

    /**
     * Get the latest service record.
     */
    public function latestService()
    {
        return $this->hasOne(VehicleServiceHistory::class)->latestOfMany('service_date');
    }

    /**
     * Get full vehicle description.
     */
    public function getDescriptionAttribute(): string
    {
        return "{$this->year} {$this->make} {$this->model}";
    }

    /**
     * Check if warranty is still valid.
     */
    public function isUnderWarranty(): bool
    {
        return $this->warranty_expiry && $this->warranty_expiry->isFuture();
    }

    /**
     * Scope active vehicles.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
