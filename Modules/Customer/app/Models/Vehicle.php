<?php

declare(strict_types=1);

namespace Modules\Customer\Models;

use App\Core\Traits\AuditTrait;
use App\Core\Traits\TenantAware;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Vehicle Model
 *
 * Represents a vehicle owned by a customer in the service center system.
 * Supports cross-branch service history tracking.
 *
 * @property int $id
 * @property int $customer_id
 * @property string $vehicle_number
 * @property string $registration_number
 * @property string|null $vin
 * @property string $make
 * @property string $model
 * @property int $year
 * @property string|null $color
 * @property string|null $engine_number
 * @property string|null $chassis_number
 * @property string|null $fuel_type
 * @property string|null $transmission
 * @property int $current_mileage
 * @property \Carbon\Carbon|null $purchase_date
 * @property \Carbon\Carbon|null $registration_date
 * @property \Carbon\Carbon|null $insurance_expiry
 * @property string|null $insurance_provider
 * @property string|null $insurance_policy_number
 * @property string $status
 * @property string|null $notes
 * @property \Carbon\Carbon|null $last_service_date
 * @property int|null $next_service_mileage
 * @property \Carbon\Carbon|null $next_service_date
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
class Vehicle extends Model
{
    use AuditTrait;
    use HasFactory;
    use SoftDeletes;
    use TenantAware;

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): \Modules\Customer\Database\Factories\VehicleFactory
    {
        return \Modules\Customer\Database\Factories\VehicleFactory::new();
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'customer_id',
        'vehicle_number',
        'registration_number',
        'vin',
        'make',
        'model',
        'year',
        'color',
        'engine_number',
        'chassis_number',
        'fuel_type',
        'transmission',
        'current_mileage',
        'purchase_date',
        'registration_date',
        'insurance_expiry',
        'insurance_provider',
        'insurance_policy_number',
        'status',
        'notes',
        'last_service_date',
        'next_service_mileage',
        'next_service_date',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'current_mileage' => 'integer',
            'next_service_mileage' => 'integer',
            'purchase_date' => 'date',
            'registration_date' => 'date',
            'insurance_expiry' => 'date',
            'last_service_date' => 'datetime',
            'next_service_date' => 'date',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    /**
     * Get the customer that owns the vehicle.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the service records for the vehicle.
     */
    public function serviceRecords(): HasMany
    {
        return $this->hasMany(VehicleServiceRecord::class);
    }

    /**
     * Get the vehicle's display name.
     */
    public function getDisplayNameAttribute(): string
    {
        return "{$this->year} {$this->make} {$this->model}";
    }

    /**
     * Check if vehicle is due for service based on mileage.
     */
    public function isDueForServiceByMileage(): bool
    {
        if (! $this->next_service_mileage) {
            return false;
        }

        return $this->current_mileage >= $this->next_service_mileage;
    }

    /**
     * Check if vehicle is due for service based on date.
     */
    public function isDueForServiceByDate(): bool
    {
        if (! $this->next_service_date) {
            return false;
        }

        return now()->greaterThanOrEqualTo($this->next_service_date);
    }

    /**
     * Check if insurance is expired or expiring soon.
     */
    public function isInsuranceExpiringSoon(int $daysThreshold = 30): bool
    {
        if (! $this->insurance_expiry) {
            return false;
        }

        return now()->diffInDays($this->insurance_expiry, false) <= $daysThreshold;
    }

    /**
     * Scope to filter active vehicles.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope to filter by make.
     */
    public function scopeByMake($query, string $make)
    {
        return $query->where('make', $make);
    }

    /**
     * Scope to filter vehicles due for service.
     */
    public function scopeDueForService($query)
    {
        return $query->where(function ($q) {
            $q->whereColumn('current_mileage', '>=', 'next_service_mileage')
                ->orWhere('next_service_date', '<=', now());
        });
    }

    /**
     * Generate a unique vehicle number.
     */
    public static function generateVehicleNumber(): string
    {
        do {
            $prefix = 'VEH';
            $timestamp = now()->format('Ymd');
            // Use random_int for better randomness and add microseconds for more entropy
            $random = str_pad((string) random_int(0, 99999), 5, '0', STR_PAD_LEFT);
            $microtime = str_pad((string) (int) (microtime(true) * 10000 % 10000), 4, '0', STR_PAD_LEFT);

            $vehicleNumber = "{$prefix}-{$timestamp}-{$random}{$microtime}";

            // Check if the number already exists
            $exists = static::where('vehicle_number', $vehicleNumber)->exists();
        } while ($exists);

        return $vehicleNumber;
    }
}
