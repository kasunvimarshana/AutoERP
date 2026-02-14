<?php

namespace App\Modules\Fleet\Models;

use App\Core\Traits\HasUuid;
use App\Core\Traits\TenantScoped;
use App\Modules\CRM\Models\Customer;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Vehicle Model
 * 
 * Represents a vehicle in the fleet management system
 */
class Vehicle extends Model
{
    use HasFactory, TenantScoped, HasUuid, SoftDeletes;

    protected $fillable = [
        'customer_id',
        'registration_number',
        'vin',
        'make',
        'model',
        'year',
        'color',
        'engine_number',
        'chassis_number',
        'odometer_reading',
        'fuel_type',
        'transmission',
        'warranty_expires_at',
        'insurance_expires_at',
        'status',
    ];

    protected $casts = [
        'year' => 'integer',
        'odometer_reading' => 'integer',
        'warranty_expires_at' => 'date',
        'insurance_expires_at' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $hidden = [];

    /**
     * Get the customer that owns this vehicle
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get service history for this vehicle
     */
    public function serviceHistory(): HasMany
    {
        return $this->hasMany(VehicleServiceHistory::class);
    }
}
