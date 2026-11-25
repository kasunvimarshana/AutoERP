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
use LogicException;
use Modules\Core\Models\TenantOwnedModel;
use Modules\Payroll\Enums\PayslipItemType;
use Modules\Payroll\Enums\PayslipStatus;
use Modules\Payroll\Models\Concerns\ScopesPayrollTenant;

final class PayrollPayslipItem extends TenantOwnedModel
{
    use ScopesPayrollTenant;

    protected $table = 'payroll_payslip_items';

    protected $guarded = ['id'];

    protected static function booted(): void
    {
        parent::booted();

        self::updating(function (self $item): void {
            $payslip = $item->payslip;
            if ($payslip !== null) {
                $status = $payslip->status;
                if (($status instanceof PayslipStatus && in_array($status, [PayslipStatus::Approved, PayslipStatus::Paid], true))
                    || in_array($status, [PayslipStatus::Approved->value, PayslipStatus::Paid->value], true)) {
                    throw new LogicException('Line items cannot be modified on approved or paid payslips.');
                }
            }
        });

        self::deleting(function (self $item): void {
            $payslip = $item->payslip;
            if ($payslip !== null) {
                $status = $payslip->status;
                if (($status instanceof PayslipStatus && in_array($status, [PayslipStatus::Approved, PayslipStatus::Paid], true))
                    || in_array($status, [PayslipStatus::Approved->value, PayslipStatus::Paid->value], true)) {
                    throw new LogicException('Line items cannot be deleted from approved or paid payslips.');
                }
            }
        });
    }

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'row_version' => 'integer',
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'payslip_id' => 'integer',
            'salary_component_id' => 'integer',
            'item_type' => PayslipItemType::class,
            'rate' => 'decimal:6',
            'hours_or_quantity' => 'decimal:2',
            'amount' => 'decimal:6',
            'is_taxable' => 'boolean',
            'is_statutory' => 'boolean',
        ]);
    }

    public function payslip(): BelongsTo
    {
        return $this->belongsTo(PayrollPayslip::class, 'payslip_id');
    }

    public function component(): BelongsTo
    {
        return $this->belongsTo(PayrollSalaryComponent::class, 'salary_component_id');
    }
}
