<?php declare(strict_types=1);

namespace Modules\Asset\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * VehicleModel - Eloquent model for vehicles table
 *
 * @package Modules\Asset\Infrastructure\Persistence\Eloquent\Models
 */
class VehicleModel extends Model
{
    use SoftDeletes;

    protected $table = 'vehicles';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'tenant_id',
        'asset_id',
        'vin',
        'registration_plate',
        'vehicle_type',
        'make',
        'model',
        'year',
        'color',
        'fuel_type',
        'transmission',
        'seating_capacity',
        'fuel_tank_capacity',
        'engine_displacement',
        'current_mileage',
        'current_location_id',
        'is_rentable',
        'is_serviceable',
        'status',
        'insurance_policy_number',
        'insurance_expiry_date',
        'last_service_date',
        'next_service_date',
        'next_service_mileage',
    ];

    protected $casts = [
        'is_rentable' => 'boolean',
        'is_serviceable' => 'boolean',
        'insurance_expiry_date' => 'date',
        'last_service_date' => 'date',
        'next_service_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // Relationships
    public function asset(): BelongsTo
    {
        return $this->belongsTo(AssetModel::class, 'asset_id', 'id');
    }

    // Scopes
    public function scopeByTenant($query, string $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('vehicle_type', $type);
    }

    public function scopeAvailableForRental($query, string $tenantId)
    {
        return $query->where('tenant_id', $tenantId)
            ->where('is_rentable', true)
            ->where('status', 'available');
    }

    public function scopeRentable($query)
    {
        return $query->where('is_rentable', true);
    }

    public function scopeServiceable($query)
    {
        return $query->where('is_serviceable', true);
    }
}
