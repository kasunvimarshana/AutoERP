<?php

namespace App\Modules\FleetManagement\Models;

use App\Modules\CustomerManagement\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class MaintenanceSchedule extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'tenant_id',
        'vehicle_id',
        'fleet_id',
        'schedule_name',
        'description',
        'schedule_type',
        'mileage_interval',
        'time_interval_days',
        'last_service_date',
        'last_service_mileage',
        'next_service_date',
        'next_service_mileage',
        'is_active',
    ];

    protected $casts = [
        'last_service_date' => 'date',
        'next_service_date' => 'date',
        'last_service_mileage' => 'integer',
        'next_service_mileage' => 'integer',
        'mileage_interval' => 'integer',
        'time_interval_days' => 'integer',
        'is_active' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($schedule) {
            if (empty($schedule->uuid)) {
                $schedule->uuid = Str::uuid();
            }
        });
    }

    /**
     * Get the tenant that owns the maintenance schedule
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Tenant::class);
    }

    /**
     * Get the vehicle that owns the maintenance schedule
     */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    /**
     * Get the fleet that owns the maintenance schedule
     */
    public function fleet(): BelongsTo
    {
        return $this->belongsTo(Fleet::class);
    }

    /**
     * Check if schedule is active
     */
    public function isActive(): bool
    {
        return $this->is_active === true;
    }

    /**
     * Check if maintenance is due based on date
     */
    public function isDueByDate(): bool
    {
        if (!$this->next_service_date) {
            return false;
        }

        return now()->gte($this->next_service_date);
    }

    /**
     * Check if maintenance is due based on mileage
     */
    public function isDueByMileage(): bool
    {
        if (!$this->next_service_mileage || !$this->vehicle) {
            return false;
        }

        return $this->vehicle->current_mileage >= $this->next_service_mileage;
    }

    /**
     * Check if maintenance is due (by date or mileage)
     */
    public function isDue(): bool
    {
        if ($this->schedule_type === 'mileage_based') {
            return $this->isDueByMileage();
        }

        if ($this->schedule_type === 'time_based') {
            return $this->isDueByDate();
        }

        return $this->isDueByDate() || $this->isDueByMileage();
    }

    /**
     * Calculate next service date
     */
    public function calculateNextServiceDate(): ?\DateTime
    {
        if (!$this->last_service_date || !$this->time_interval_days) {
            return null;
        }

        return $this->last_service_date->copy()->addDays($this->time_interval_days);
    }

    /**
     * Calculate next service mileage
     */
    public function calculateNextServiceMileage(): ?int
    {
        if (!$this->last_service_mileage || !$this->mileage_interval) {
            return null;
        }

        return $this->last_service_mileage + $this->mileage_interval;
    }

    /**
     * Update schedule after service completion
     */
    public function updateAfterService(int $serviceMileage): void
    {
        $data = [
            'last_service_date' => now(),
            'last_service_mileage' => $serviceMileage,
        ];

        if ($this->time_interval_days) {
            $data['next_service_date'] = now()->addDays($this->time_interval_days);
        }

        if ($this->mileage_interval) {
            $data['next_service_mileage'] = $serviceMileage + $this->mileage_interval;
        }

        $this->update($data);
    }

    /**
     * Activate the schedule
     */
    public function activate(): void
    {
        $this->update(['is_active' => true]);
    }

    /**
     * Deactivate the schedule
     */
    public function deactivate(): void
    {
        $this->update(['is_active' => false]);
    }

    /**
     * Get days until next service
     */
    public function getDaysUntilServiceAttribute(): ?int
    {
        if (!$this->next_service_date) {
            return null;
        }

        return now()->diffInDays($this->next_service_date, false);
    }

    /**
     * Get mileage until next service
     */
    public function getMileageUntilServiceAttribute(): ?int
    {
        if (!$this->next_service_mileage || !$this->vehicle) {
            return null;
        }

        return $this->next_service_mileage - $this->vehicle->current_mileage;
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
     * Scope: Active schedules
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Inactive schedules
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
     * Scope: By vehicle
     */
    public function scopeForVehicle($query, int $vehicleId)
    {
        return $query->where('vehicle_id', $vehicleId);
    }

    /**
     * Scope: By fleet
     */
    public function scopeForFleet($query, int $fleetId)
    {
        return $query->where('fleet_id', $fleetId);
    }

    /**
     * Scope: Due schedules
     */
    public function scopeDue($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->where('next_service_date', '<=', now())
                    ->orWhereRaw('next_service_mileage <= (SELECT current_mileage FROM vehicles WHERE vehicles.id = maintenance_schedules.vehicle_id)');
            });
    }

    /**
     * Scope: By schedule type
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('schedule_type', $type);
    }
}
