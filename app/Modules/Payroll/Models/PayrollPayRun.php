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
use Modules\Finance\Models\FinanceJournalEntry;
use Modules\Payroll\Enums\PayRunStatus;
use Modules\Payroll\Enums\PayRunType;
use Modules\Payroll\Models\Concerns\ScopesPayrollTenant;
use Modules\User\Models\UserModel;

final class PayrollPayRun extends TenantOwnedModel
{
    use ScopesPayrollTenant;

    protected $table = 'payroll_pay_runs';

    protected $guarded = ['id'];

    protected static function booted(): void
    {
        parent::booted();

        self::updating(function (self $payRun): void {
            $originalStatus = $payRun->getOriginal('status');
            $isFinalized = ($originalStatus instanceof PayRunStatus && in_array($originalStatus, [PayRunStatus::Approved, PayRunStatus::Paid], true))
                || in_array($originalStatus, [PayRunStatus::Approved->value, PayRunStatus::Paid->value], true)
                || in_array($payRun->getRawOriginal('status'), [PayRunStatus::Approved->value, PayRunStatus::Paid->value], true);

            if ($isFinalized) {
                $allowedDirty = [
                    'status',
                    'paid_at',
                    'paid_by',
                    'reversed_at',
                    'reversed_by',
                    'reversal_reason',
                    'finance_journal_id',
                    'row_version',
                    'updated_at',
                ];
                $disallowed = array_diff(array_keys($payRun->getDirty()), $allowedDirty);

                if ($disallowed !== []) {
                    throw new LogicException('Approved or Paid pay runs cannot have their operational totals modified in place.');
                }
            }

            if (! $payRun->isDirty('row_version')) {
                $payRun->row_version = ((int) $payRun->getOriginal('row_version', 1)) + 1;
            }
        });

        self::deleting(function (self $payRun): void {
            $status = $payRun->status;
            $isFinalized = ($status instanceof PayRunStatus && in_array($status, [PayRunStatus::Approved, PayRunStatus::Paid], true))
                || in_array($status, [PayRunStatus::Approved->value, PayRunStatus::Paid->value], true);

            if ($isFinalized) {
                throw new LogicException('Approved or Paid pay runs cannot be deleted.');
            }
        });
    }

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'row_version' => 'integer',
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'pay_period_id' => 'integer',
            'salary_structure_id' => 'integer',
            'pay_run_type' => PayRunType::class,
            'status' => PayRunStatus::class,
            'total_gross_pay' => 'decimal:6',
            'total_deductions' => 'decimal:6',
            'total_employee_statutory' => 'decimal:6',
            'total_employer_statutory' => 'decimal:6',
            'total_tax' => 'decimal:6',
            'total_net_pay' => 'decimal:6',
            'total_employees' => 'integer',
            'calculated_by' => 'integer',
            'calculated_at' => 'datetime',
            'approved_by' => 'integer',
            'approved_at' => 'datetime',
            'paid_by' => 'integer',
            'paid_at' => 'datetime',
            'reversed_by' => 'integer',
            'reversed_at' => 'datetime',
            'finance_journal_id' => 'integer',
        ]);
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(PayrollPayPeriod::class, 'pay_period_id');
    }

    public function structure(): BelongsTo
    {
        return $this->belongsTo(PayrollSalaryStructure::class, 'salary_structure_id');
    }

    public function payslips(): HasMany
    {
        return $this->hasMany(PayrollPayslip::class, 'pay_run_id');
    }

    public function calculatedByUser(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'calculated_by');
    }

    public function approvedByUser(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'approved_by');
    }

    public function paidByUser(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'paid_by');
    }

    public function reversedByUser(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'reversed_by');
    }

    public function financeJournal(): BelongsTo
    {
        return $this->belongsTo(FinanceJournalEntry::class, 'finance_journal_id');
    }
}
