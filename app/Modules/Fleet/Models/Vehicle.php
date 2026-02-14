<?php

namespace App\Modules\Fleet\Models;

use App\Core\Traits\TenantScoped;
use App\Modules\Branch\Models\Branch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Vehicle Model
 *
 * Represents vehicles in the fleet management system
 */
class Vehicle extends Model
{
    use SoftDeletes, TenantScoped;

    protected $fillable = [
        'tenant_id',
        'branch_id',
        'registration_number',
        'vin',
        'make',
        'model',
        'year',
        'color',
        'fuel_type',
        'transmission',
        'mileage',
        'purchase_date',
        'purchase_price',
        'status',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'year' => 'integer',
        'mileage' => 'integer',
        'purchase_date' => 'date',
        'purchase_price' => 'decimal:2',
        'metadata' => 'array',
    ];

    /**
     * Get the tenant that owns the vehicle
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Tenancy\Models\Tenant::class);
    }

    /**
     * Get the branch assigned to this vehicle
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get maintenance records for this vehicle
     */
    public function maintenanceRecords(): HasMany
    {
        return $this->hasMany(MaintenanceRecord::class);
    }
}
