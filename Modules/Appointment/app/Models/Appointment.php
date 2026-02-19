<?php

declare(strict_types=1);

namespace Modules\Appointment\Models;

use App\Core\Traits\AuditTrait;
use App\Core\Traits\TenantAware;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Customer\Models\Customer;
use Modules\Customer\Models\Vehicle;
use Modules\Organization\Models\Branch;

/**
 * Appointment Model
 *
 * Represents a service appointment for a vehicle
 *
 * @property int $id
 * @property string $appointment_number
 * @property int $customer_id
 * @property int $vehicle_id
 * @property int $branch_id
 * @property string $service_type
 * @property \Carbon\Carbon $scheduled_date_time
 * @property int $duration
 * @property string $status
 * @property string|null $notes
 * @property string|null $customer_notes
 * @property int|null $assigned_technician_id
 * @property \Carbon\Carbon|null $confirmed_at
 * @property \Carbon\Carbon|null $started_at
 * @property \Carbon\Carbon|null $completed_at
 * @property \Carbon\Carbon|null $cancelled_at
 * @property string|null $cancellation_reason
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
class Appointment extends Model
{
    use AuditTrait;
    use HasFactory;
    use SoftDeletes;
    use TenantAware;

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): \Modules\Appointment\Database\Factories\AppointmentFactory
    {
        return \Modules\Appointment\Database\Factories\AppointmentFactory::new();
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'appointment_number',
        'customer_id',
        'vehicle_id',
        'branch_id',
        'service_type',
        'scheduled_date_time',
        'duration',
        'status',
        'notes',
        'customer_notes',
        'assigned_technician_id',
        'confirmed_at',
        'started_at',
        'completed_at',
        'cancelled_at',
        'cancellation_reason',
        'created_by',
        'updated_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'scheduled_date_time' => 'datetime',
            'duration' => 'integer',
            'confirmed_at' => 'datetime',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    /**
     * Get the customer that owns the appointment.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the vehicle for the appointment.
     */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    /**
     * Get the branch for the appointment.
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get the assigned technician.
     */
    public function assignedTechnician(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_technician_id');
    }

    /**
     * Get the bay schedules for the appointment.
     */
    public function baySchedules(): HasMany
    {
        return $this->hasMany(BaySchedule::class);
    }

    /**
     * Scope to filter by status.
     */
    public function scopeOfStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to filter by branch.
     */
    public function scopeForBranch($query, int $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    /**
     * Scope to filter by date range.
     */
    public function scopeInDateRange($query, string $startDate, string $endDate)
    {
        return $query->whereBetween('scheduled_date_time', [$startDate, $endDate]);
    }

    /**
     * Scope to filter upcoming appointments.
     */
    public function scopeUpcoming($query)
    {
        return $query->where('scheduled_date_time', '>=', now())
            ->whereIn('status', ['scheduled', 'confirmed']);
    }

    /**
     * Generate a unique appointment number.
     */
    public static function generateAppointmentNumber(): string
    {
        $prefix = 'APT';
        $timestamp = now()->format('Ymd');
        $random = str_pad((string) rand(0, 9999), 4, '0', STR_PAD_LEFT);

        return "{$prefix}-{$timestamp}-{$random}";
    }
}
