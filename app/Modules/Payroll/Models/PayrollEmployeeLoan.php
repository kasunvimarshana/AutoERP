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

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;
use Modules\Core\Models\TenantOwnedModel;
use Modules\Hr\Models\HrEmployee;
use Modules\Payroll\Enums\LoanStatus;
use Modules\Payroll\Enums\LoanType;
use Modules\Payroll\Models\Concerns\ScopesPayrollTenant;
use Modules\User\Models\UserModel;

final class PayrollEmployeeLoan extends TenantOwnedModel
{
    use ScopesPayrollTenant;

    protected $table = 'payroll_employee_loans';

    protected $guarded = ['id'];

    protected $casts = [
        'loan_type' => LoanType::class,
        'status' => LoanStatus::class,
        'principal_amount' => 'decimal:6',
        'annual_interest_rate' => 'decimal:2',
        'term_months' => 'integer',
        'monthly_installment' => 'decimal:6',
        'total_payable' => 'decimal:6',
        'total_paid' => 'decimal:6',
        'balance_remaining' => 'decimal:6',
        'disbursed_date' => 'date',
        'approved_at' => 'datetime',
        'row_version' => 'integer',
    ];

    protected static function booted(): void
    {
        parent::booted();

        self::updating(function (self $loan): void {
            $originalStatus = $loan->getOriginal('status');
            $originalStatusEnum = $originalStatus instanceof LoanStatus
                ? $originalStatus
                : LoanStatus::tryFrom((string) ($originalStatus ?? $loan->getRawOriginal('status')));

            $immutableTerms = [
                'tenant_id',
                'employee_id',
                'loan_type',
                'principal_amount',
                'annual_interest_rate',
                'term_months',
                'monthly_installment',
                'total_payable',
            ];

            if (
                in_array($originalStatusEnum, [LoanStatus::Active, LoanStatus::Completed], true)
                && $loan->isDirty($immutableTerms)
            ) {
                throw new LogicException(sprintf(
                    'Cannot alter financial terms of %s loan.',
                    $originalStatusEnum === LoanStatus::Completed ? 'a completed' : 'an active',
                ));
            }

            if (! $loan->isDirty('row_version')) {
                $loan->row_version = ((int) $loan->getOriginal('row_version', 1)) + 1;
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
        return $this->belongsTo(UserModel::class, 'approved_by');
    }

    public function repayments(): HasMany
    {
        return $this->hasMany(PayrollLoanRepayment::class, 'payroll_employee_loan_id');
    }
}
