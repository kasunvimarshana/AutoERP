<?php

declare(strict_types=1);

namespace Modules\HR\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Traits\Auditable;
use Modules\Core\Traits\Tenantable;
use Modules\HR\Database\Factories\AttendanceFactory;

/**
 * Attendance Entity
 *
 * Represents daily attendance records for employees.
 * Tracks check-in/out times and work hours.
 *
 * @property int $id
 * @property int $tenant_id
 * @property int $employee_id
 * @property \Illuminate\Support\Carbon $date
 * @property \Illuminate\Support\Carbon|null $check_in
 * @property \Illuminate\Support\Carbon|null $check_out
 * @property float|null $work_hours
 * @property string $status
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
class Attendance extends Model
{
    use Auditable, HasFactory, SoftDeletes, Tenantable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'tenant_id',
        'employee_id',
        'date',
        'check_in',
        'check_out',
        'work_hours',
        'status',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'date' => 'date',
        'check_in' => 'datetime',
        'check_out' => 'datetime',
        'work_hours' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): AttendanceFactory
    {
        return AttendanceFactory::new();
    }

    /**
     * Get the employee that owns the attendance.
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Calculate work hours based on check-in and check-out times.
     */
    public function calculateWorkHours(): ?float
    {
        if ($this->check_in && $this->check_out) {
            $hours = $this->check_in->diffInMinutes($this->check_out) / 60;

            return round($hours, 2);
        }

        return null;
    }

    /**
     * Check if the attendance is complete (has both check-in and check-out).
     */
    public function isComplete(): bool
    {
        return $this->check_in !== null && $this->check_out !== null;
    }
}
