<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Payroll\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;
use Modules\Hr\Models\HrEmployee;
use Modules\Payroll\Enums\LoanStatus;
use Modules\Payroll\Enums\LoanType;
use Modules\Payroll\Models\Concerns\ScopesPayrollTenant;

final class PayrollEmployeeLoan extends Model
{
    use ScopesPayrollTenant;

    protected $table = 'payroll_employee_loans';

    protected $guarded = ['id'];

    protected $casts = [
        'loan_type' => LoanType::class,
        'status' => LoanStatus::class,
        'principal_amount' => 'decimal:4',
        'annual_interest_rate' => 'decimal:2',
        'term_months' => 'integer',
        'monthly_installment' => 'decimal:4',
        'total_payable' => 'decimal:4',
        'total_paid' => 'decimal:4',
        'balance_remaining' => 'decimal:4',
        'disbursed_date' => 'date',
        'approved_at' => 'datetime',
        'row_version' => 'integer',
    ];

    protected static function booted(): void
    {
        self::updating(function (self $loan): void {
            $originalStatus = $loan->getOriginal('status');
            $originalStatusEnum = $originalStatus instanceof LoanStatus ? $originalStatus : LoanStatus::tryFrom((string) $originalStatus);

            if ($originalStatusEnum === LoanStatus::Completed && $loan->isDirty(['principal_amount', 'term_months', 'monthly_installment'])) {
                throw new LogicException('Cannot alter financial terms of a completed loan.');
            }
        });

        self::deleting(function (self $loan): void {
            if ($loan->status !== LoanStatus::Draft && $loan->status !== LoanStatus::Cancelled) {
                throw new LogicException(sprintf('Cannot delete loan in "%s" status. Cancel it instead.', $loan->status->value));
            }
        });
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(HrEmployee::class, 'employee_id');
    }

    public function deductionStartPeriod(): BelongsTo
    {
        return $this->belongsTo(PayrollPayPeriod::class, 'deduction_start_period_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function repayments(): HasMany
    {
        return $this->hasMany(PayrollLoanRepayment::class, 'payroll_employee_loan_id');
    }
}
