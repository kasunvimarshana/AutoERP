<?php

namespace App\Modules\FleetManagement\Models;

use App\Modules\CustomerManagement\Models\Customer;
use App\Modules\CustomerManagement\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Fleet extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'tenant_id',
        'customer_id',
        'fleet_name',
        'description',
        'total_vehicles',
        'fleet_manager_name',
        'fleet_manager_email',
        'fleet_manager_phone',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'total_vehicles' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($fleet) {
            if (empty($fleet->uuid)) {
                $fleet->uuid = Str::uuid();
            }
        });
    }

    /**
     * Get the tenant that owns the fleet
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Tenant::class);
    }

    /**
     * Get the customer that owns the fleet
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the vehicles in the fleet through pivot table
     */
    public function vehicles(): BelongsToMany
    {
        return $this->belongsToMany(Vehicle::class, 'fleet_vehicles')
            ->withPivot('assigned_date', 'removed_date', 'is_active')
            ->withTimestamps();
    }

    /**
     * Get the fleet vehicles pivot records
     */
    public function fleetVehicles(): HasMany
    {
        return $this->hasMany(FleetVehicle::class);
    }

    /**
     * Get the maintenance schedules for this fleet
     */
    public function maintenanceSchedules(): HasMany
    {
        return $this->hasMany(MaintenanceSchedule::class);
    }

    /**
     * Get active vehicles in the fleet
     */
    public function activeVehicles(): BelongsToMany
    {
        return $this->vehicles()->wherePivot('is_active', true);
    }

    /**
     * Check if fleet is active
     */
    public function isActive(): bool
    {
        return $this->is_active === true;
    }

    /**
     * Activate the fleet
     */
    public function activate(): void
    {
        $this->update(['is_active' => true]);
    }

    /**
     * Deactivate the fleet
     */
    public function deactivate(): void
    {
        $this->update(['is_active' => false]);
    }

    /**
     * Update total vehicles count
     */
    public function updateVehicleCount(): void
    {
        $this->update([
            'total_vehicles' => $this->fleetVehicles()->where('is_active', true)->count(),
        ]);
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
     * Scope: Active fleets
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Inactive fleets
     */
    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    /**
     * Scope: By tenant
     */
    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope: By customer
     */
    public function scopeForCustomer($query, int $customerId)
    {
        return $query->where('customer_id', $customerId);
    }
}
