<?php

declare(strict_types=1);

namespace Modules\JobCard\Models;

use App\Core\Traits\AuditTrait;
use App\Core\Traits\TenantAware;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Appointment\Models\Appointment;
use Modules\Customer\Models\Customer;
use Modules\Customer\Models\Vehicle;
use Modules\Organization\Models\Branch;

/**
 * JobCard Model
 *
 * Represents a job card for vehicle service workflow
 *
 * @property int $id
 * @property int|null $appointment_id
 * @property int $vehicle_id
 * @property int $customer_id
 * @property int $branch_id
 * @property string $job_number
 * @property string $status
 * @property string $priority
 * @property int|null $technician_id
 * @property int|null $supervisor_id
 * @property float|null $estimated_hours
 * @property float|null $actual_hours
 * @property float $parts_total
 * @property float $labor_total
 * @property float $grand_total
 * @property \Carbon\Carbon|null $started_at
 * @property \Carbon\Carbon|null $completed_at
 * @property string|null $notes
 * @property string|null $customer_complaints
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
class JobCard extends Model
{
    use AuditTrait;
    use HasFactory;
    use SoftDeletes;
    use TenantAware;

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): \Modules\JobCard\Database\Factories\JobCardFactory
    {
        return \Modules\JobCard\Database\Factories\JobCardFactory::new();
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'appointment_id',
        'vehicle_id',
        'customer_id',
        'branch_id',
        'job_number',
        'status',
        'priority',
        'technician_id',
        'supervisor_id',
        'estimated_hours',
        'actual_hours',
        'parts_total',
        'labor_total',
        'grand_total',
        'started_at',
        'completed_at',
        'notes',
        'customer_complaints',
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
            'estimated_hours' => 'decimal:2',
            'actual_hours' => 'decimal:2',
            'parts_total' => 'decimal:2',
            'labor_total' => 'decimal:2',
            'grand_total' => 'decimal:2',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    /**
     * Get the appointment that owns the job card.
     */
    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    /**
     * Get the vehicle for the job card.
     */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    /**
     * Get the customer for the job card.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the branch for the job card.
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get the assigned technician.
     */
    public function technician(): BelongsTo
    {
        return $this->belongsTo(User::class, 'technician_id');
    }

    /**
     * Get the supervisor.
     */
    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }

    /**
     * Get the tasks for the job card.
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(JobTask::class);
    }

    /**
     * Get the inspection items for the job card.
     */
    public function inspectionItems(): HasMany
    {
        return $this->hasMany(InspectionItem::class);
    }

    /**
     * Get the parts for the job card.
     */
    public function parts(): HasMany
    {
        return $this->hasMany(JobPart::class);
    }

    /**
     * Scope to filter by status.
     */
    public function scopeOfStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to filter by priority.
     */
    public function scopeOfPriority($query, string $priority)
    {
        return $query->where('priority', $priority);
    }

    /**
     * Scope to filter by branch.
     */
    public function scopeForBranch($query, int $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    /**
     * Scope to filter by technician.
     */
    public function scopeForTechnician($query, int $technicianId)
    {
        return $query->where('technician_id', $technicianId);
    }

    /**
     * Scope to filter active job cards.
     */
    public function scopeActive($query)
    {
        return $query->whereNotIn('status', ['completed', 'cancelled']);
    }

    /**
     * Generate a unique job number.
     */
    public static function generateJobNumber(): string
    {
        $prefix = 'JOB';
        $timestamp = now()->format('Ymd');
        $random = str_pad((string) rand(0, 9999), 4, '0', STR_PAD_LEFT);

        return "{$prefix}-{$timestamp}-{$random}";
    }
}
