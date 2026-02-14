<?php

namespace App\Modules\CRM\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Vehicle Service History Model
 *
 * Centralized service history across all branches.
 * Provides complete vehicle maintenance trail.
 */
class VehicleServiceHistory extends Model
{
    protected $table = 'vehicle_service_history';

    protected $fillable = [
        'vehicle_id',
        'branch_id',
        'service_date',
        'service_type',
        'odometer_at_service',
        'description',
        'cost',
        'performed_by',
        'parts_used',
    ];

    protected $casts = [
        'service_date' => 'date',
        'odometer_at_service' => 'integer',
        'cost' => 'decimal:2',
        'parts_used' => 'array',
    ];

    /**
     * Get the vehicle that owns the service record.
     */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    /**
     * Get the branch where service was performed.
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Tenant\Models\Branch::class);
    }
}
