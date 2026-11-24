<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Payroll\Services;

use Modules\Payroll\DTOs\CalculatedLineItemDTO;
use Modules\Payroll\Enums\PayslipItemType;
use Modules\Payroll\Models\PayrollStatutoryRule;

final class PayrollStatutoryService
{
    /**
     * @return array{
     *     total_employee_deduction: string,
     *     total_employer_contribution: string,
     *     items: list<CalculatedLineItemDTO>
     * }
     */
    public function calculateStatutory(int $tenantId, string $statutoryBase): array
    {
        $rules = PayrollStatutoryRule::query()
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->get();

        $totalEmployee = '0.0000';
        $totalEmployer = '0.0000';
        $items = [];

        foreach ($rules as $rule) {
            $eeRate = bcdiv((string) $rule->employee_rate_percentage, '100', 4);
            $erRate = bcdiv((string) $rule->employer_rate_percentage, '100', 4);

            $eeAmount = bcmul($statutoryBase, $eeRate, 4);
            $erAmount = bcmul($statutoryBase, $erRate, 4);

            if (bccomp($eeAmount, '0.0000', 4) > 0) {
                $totalEmployee = bcadd($totalEmployee, $eeAmount, 4);
                $items[] = new CalculatedLineItemDTO(
                    itemType: PayslipItemType::EmployeeStatutory,
                    itemCode: $rule->code.'_EE',
                    itemName: $rule->name.' (Employee)',
                    rate: (string) $rule->employee_rate_percentage,
                    hoursOrQuantity: '1.00',
                    amount: $eeAmount,
                    isTaxable: false,
                    isStatutory: true,
                    calculationBasis: sprintf('%.2f%% of %s', (float) $rule->employee_rate_percentage, $statutoryBase),
                );
            }

            if (bccomp($erAmount, '0.0000', 4) > 0) {
                $totalEmployer = bcadd($totalEmployer, $erAmount, 4);
                $items[] = new CalculatedLineItemDTO(
                    itemType: PayslipItemType::EmployerStatutory,
                    itemCode: $rule->code.'_ER',
                    itemName: $rule->name.' (Employer Liability)',
                    rate: (string) $rule->employer_rate_percentage,
                    hoursOrQuantity: '1.00',
                    amount: $erAmount,
                    isTaxable: false,
                    isStatutory: true,
                    calculationBasis: sprintf('%.2f%% of %s', (float) $rule->employer_rate_percentage, $statutoryBase),
                );
            }
        }

        return [
            'total_employee_deduction' => $totalEmployee,
            'total_employer_contribution' => $totalEmployer,
            'items' => $items,
        ];
    }
}
