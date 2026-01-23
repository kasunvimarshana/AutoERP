<?php

namespace App\Modules\FleetManagement\Models;

use App\Modules\CustomerManagement\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class FleetVehicle extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'fleet_id',
        'vehicle_id',
        'assigned_date',
        'removed_date',
        'is_active',
    ];

    protected $casts = [
        'assigned_date' => 'date',
        'removed_date' => 'date',
        'is_active' => 'boolean',
    ];

    /**
     * Get the fleet that owns the fleet vehicle
     */
    public function fleet(): BelongsTo
    {
        return $this->belongsTo(Fleet::class);
    }

    /**
     * Get the vehicle associated with the fleet
     */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    /**
     * Check if vehicle is currently active in fleet
     */
    public function isActive(): bool
    {
        return $this->is_active === true && is_null($this->removed_date);
    }

    /**
     * Remove vehicle from fleet
     */
    public function remove(): void
    {
        $this->update([
            'is_active' => false,
            'removed_date' => now(),
        ]);
    }

    /**
     * Reactivate vehicle in fleet
     */
    public function reactivate(): void
    {
        $this->update([
            'is_active' => true,
            'removed_date' => null,
        ]);
    }

    /**
     * Get days in fleet
     */
    public function getDaysInFleetAttribute(): int
    {
        $endDate = $this->removed_date ?? now();
        return $this->assigned_date->diffInDays($endDate);
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
     * Scope: Active fleet vehicles
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->whereNull('removed_date');
    }

    /**
     * Scope: By fleet
     */
    public function scopeForFleet($query, int $fleetId)
    {
        return $query->where('fleet_id', $fleetId);
    }

    /**
     * Scope: By vehicle
     */
    public function scopeForVehicle($query, int $vehicleId)
    {
        return $query->where('vehicle_id', $vehicleId);
    }
}
