<?php

declare(strict_types=1);

namespace Modules\HR\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Traits\Auditable;
use Modules\Core\Traits\Tenantable;
use Modules\HR\Database\Factories\EmployeeFactory;

/**
 * Employee Entity
 *
 * Represents an employee in the organization.
 * Central entity that links to departments, positions, attendance, leave, payroll, etc.
 *
 * @property int $id
 * @property int $tenant_id
 * @property string $employee_number
 * @property string $first_name
 * @property string $last_name
 * @property string $email
 * @property string|null $phone
 * @property string|null $mobile
 * @property string|null $date_of_birth
 * @property string|null $gender
 * @property string|null $address
 * @property string|null $city
 * @property string|null $state
 * @property string|null $postal_code
 * @property string|null $country
 * @property int|null $department_id
 * @property int|null $position_id
 * @property int|null $reports_to
 * @property \Illuminate\Support\Carbon $hire_date
 * @property \Illuminate\Support\Carbon|null $termination_date
 * @property string $employment_type
 * @property string $status
 * @property float|null $salary
 * @property string|null $bank_account
 * @property string|null $tax_id
 * @property array|null $emergency_contact
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
class Employee extends Model
{
    use Auditable, HasFactory, SoftDeletes, Tenantable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'tenant_id',
        'employee_number',
        'first_name',
        'last_name',
        'email',
        'phone',
        'mobile',
        'date_of_birth',
        'gender',
        'address',
        'city',
        'state',
        'postal_code',
        'country',
        'department_id',
        'position_id',
        'reports_to',
        'hire_date',
        'termination_date',
        'employment_type',
        'status',
        'salary',
        'bank_account',
        'tax_id',
        'emergency_contact',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'date_of_birth' => 'date',
        'hire_date' => 'date',
        'termination_date' => 'date',
        'salary' => 'decimal:2',
        'emergency_contact' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): EmployeeFactory
    {
        return EmployeeFactory::new();
    }

    /**
     * Get the employee's department.
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Get the employee's position.
     */
    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    /**
     * Get the employee's manager.
     */
    public function manager(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'reports_to');
    }

    /**
     * Get the employee's subordinates.
     */
    public function subordinates(): HasMany
    {
        return $this->hasMany(Employee::class, 'reports_to');
    }

    /**
     * Get employee's attendance records.
     */
    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    /**
     * Get employee's leave requests.
     */
    public function leaves(): HasMany
    {
        return $this->hasMany(Leave::class);
    }

    /**
     * Get employee's payroll records.
     */
    public function payrolls(): HasMany
    {
        return $this->hasMany(Payroll::class);
    }

    /**
     * Get employee's performance reviews.
     */
    public function performanceReviews(): HasMany
    {
        return $this->hasMany(PerformanceReview::class);
    }

    /**
     * Get the employee's full name.
     */
    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    /**
     * Check if employee is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if employee is terminated.
     */
    public function isTerminated(): bool
    {
        return $this->status === 'terminated' && $this->termination_date !== null;
    }
}
