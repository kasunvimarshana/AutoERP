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

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;
use Modules\Payroll\Enums\LoanRepaymentStatus;
use Modules\Payroll\Models\Concerns\ScopesPayrollTenant;

final class PayrollLoanRepayment extends Model
{
    use ScopesPayrollTenant;

    protected $table = 'payroll_loan_repayments';

    protected $guarded = ['id'];

    protected $casts = [
        'status' => LoanRepaymentStatus::class,
        'repayment_date' => 'date',
        'principal_amount' => 'decimal:4',
        'interest_amount' => 'decimal:4',
        'total_repaid' => 'decimal:4',
        'balance_after' => 'decimal:4',
        'row_version' => 'integer',
    ];

    protected static function booted(): void
    {
        self::deleting(function (self $repayment): void {
            if ($repayment->status === LoanRepaymentStatus::Deducted) {
                throw new LogicException('Deducted loan repayment records are immutable audit records and cannot be deleted.');
            }
        });
    }

    public function loan(): BelongsTo
    {
        return $this->belongsTo(PayrollEmployeeLoan::class, 'payroll_employee_loan_id');
    }

    public function payRun(): BelongsTo
    {
        return $this->belongsTo(PayrollPayRun::class, 'pay_run_id');
    }

    public function payslip(): BelongsTo
    {
        return $this->belongsTo(PayrollPayslip::class, 'payslip_id');
    }
}
