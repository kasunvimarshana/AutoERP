<?php

namespace App\Modules\Fleet\Models;

use App\Models\User;
use App\Modules\Tenancy\Models\Branch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * VehicleServiceHistory Model
 * 
 * Represents a service record for a vehicle
 */
class VehicleServiceHistory extends Model
{
    use HasFactory;

    protected $table = 'vehicle_service_history';

    protected $fillable = [
        'vehicle_id',
        'branch_id',
        'service_date',
        'service_type',
        'odometer_reading',
        'description',
        'parts_used',
        'labor_cost',
        'parts_cost',
        'total_cost',
        'performed_by',
    ];

    protected $casts = [
        'service_date' => 'date',
        'odometer_reading' => 'integer',
        'labor_cost' => 'decimal:2',
        'parts_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $hidden = [];

    /**
     * Get the vehicle for this service record
     */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    /**
     * Get the branch where service was performed
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get the user who performed the service
     */
    public function performedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }
}
