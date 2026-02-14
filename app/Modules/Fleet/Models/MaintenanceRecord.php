<?php

namespace App\Modules\Fleet\Models;

use App\Core\Traits\TenantScoped;
use App\Modules\Vendor\Models\Vendor;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Maintenance Record Model
 *
 * Represents maintenance and service records for vehicles
 */
class MaintenanceRecord extends Model
{
    use SoftDeletes, TenantScoped;

    protected $fillable = [
        'tenant_id',
        'vehicle_id',
        'vendor_id',
        'type',
        'description',
        'service_date',
        'next_service_date',
        'mileage',
        'cost',
        'parts_cost',
        'labor_cost',
        'status',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'service_date' => 'date',
        'next_service_date' => 'date',
        'mileage' => 'integer',
        'cost' => 'decimal:2',
        'parts_cost' => 'decimal:2',
        'labor_cost' => 'decimal:2',
        'metadata' => 'array',
    ];

    /**
     * Get the tenant that owns the maintenance record
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Tenancy\Models\Tenant::class);
    }

    /**
     * Get the vehicle
     */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    /**
     * Get the vendor who performed the service
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }
}
