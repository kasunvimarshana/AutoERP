<?php

declare(strict_types=1);

namespace Modules\Customer\Models;

use App\Core\Traits\AuditTrait;
use App\Core\Traits\TenantAware;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * VehicleServiceRecord Model
 *
 * Tracks service history for vehicles across all branches.
 * Supports cross-branch service tracking for comprehensive vehicle lifecycle management.
 *
 * @property int $id
 * @property int $vehicle_id
 * @property int $customer_id
 * @property string $service_number
 * @property string|null $branch_id
 * @property \Carbon\Carbon $service_date
 * @property int $mileage_at_service
 * @property string $service_type
 * @property string|null $service_description
 * @property string|null $parts_used
 * @property float $labor_cost
 * @property float $parts_cost
 * @property float $total_cost
 * @property string|null $technician_name
 * @property int|null $technician_id
 * @property string|null $notes
 * @property int|null $next_service_mileage
 * @property \Carbon\Carbon|null $next_service_date
 * @property string $status
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
class VehicleServiceRecord extends Model
{
    use AuditTrait;
    use HasFactory;
    use SoftDeletes;
    use TenantAware;

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): \Modules\Customer\Database\Factories\VehicleServiceRecordFactory
    {
        return \Modules\Customer\Database\Factories\VehicleServiceRecordFactory::new();
    }

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'vehicle_service_records';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'vehicle_id',
        'customer_id',
        'service_number',
        'branch_id',
        'service_date',
        'mileage_at_service',
        'service_type',
        'service_description',
        'parts_used',
        'labor_cost',
        'parts_cost',
        'total_cost',
        'technician_name',
        'technician_id',
        'notes',
        'next_service_mileage',
        'next_service_date',
        'status',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'vehicle_id' => 'integer',
            'customer_id' => 'integer',
            'technician_id' => 'integer',
            'service_date' => 'date',
            'mileage_at_service' => 'integer',
            'labor_cost' => 'decimal:2',
            'parts_cost' => 'decimal:2',
            'total_cost' => 'decimal:2',
            'next_service_mileage' => 'integer',
            'next_service_date' => 'date',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    /**
     * Get the vehicle that this service record belongs to.
     */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    /**
     * Get the customer that this service record belongs to.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Scope to filter by branch.
     */
    public function scopeByBranch($query, string $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    /**
     * Scope to filter by service type.
     */
    public function scopeByServiceType($query, string $serviceType)
    {
        return $query->where('service_type', $serviceType);
    }

    /**
     * Scope to filter by status.
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to get completed services.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Generate a unique service number.
     */
    public static function generateServiceNumber(): string
    {
        $prefix = 'SVC';
        $timestamp = now()->format('Ymd');
        $random = str_pad((string) rand(0, 9999), 4, '0', STR_PAD_LEFT);

        return "{$prefix}-{$timestamp}-{$random}";
    }
}
