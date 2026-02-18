<?php

declare(strict_types=1);

namespace Modules\HR\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Traits\Auditable;
use Modules\Core\Traits\Tenantable;
use Modules\HR\Database\Factories\PayrollFactory;

/**
 * Payroll Entity
 *
 * Represents monthly payroll entries for employees.
 * Includes salary, allowances, deductions, and net pay calculations.
 *
 * @property int $id
 * @property int $tenant_id
 * @property int $employee_id
 * @property string $payroll_number
 * @property int $month
 * @property int $year
 * @property float $basic_salary
 * @property float $allowances
 * @property float $deductions
 * @property float $gross_salary
 * @property float $net_salary
 * @property array|null $allowance_details
 * @property array|null $deduction_details
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $paid_at
 * @property string|null $payment_method
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
class Payroll extends Model
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
        'payroll_number',
        'month',
        'year',
        'basic_salary',
        'allowances',
        'deductions',
        'gross_salary',
        'net_salary',
        'allowance_details',
        'deduction_details',
        'status',
        'paid_at',
        'payment_method',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'month' => 'integer',
        'year' => 'integer',
        'basic_salary' => 'decimal:2',
        'allowances' => 'decimal:2',
        'deductions' => 'decimal:2',
        'gross_salary' => 'decimal:2',
        'net_salary' => 'decimal:2',
        'allowance_details' => 'array',
        'deduction_details' => 'array',
        'paid_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): PayrollFactory
    {
        return PayrollFactory::new();
    }

    /**
     * Get the employee for this payroll.
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Calculate gross salary (basic + allowances).
     */
    public function calculateGrossSalary(): float
    {
        return round($this->basic_salary + $this->allowances, 2);
    }

    /**
     * Calculate net salary (gross - deductions).
     */
    public function calculateNetSalary(): float
    {
        return round($this->calculateGrossSalary() - $this->deductions, 2);
    }

    /**
     * Check if payroll is paid.
     */
    public function isPaid(): bool
    {
        return $this->status === 'paid' && $this->paid_at !== null;
    }
}
