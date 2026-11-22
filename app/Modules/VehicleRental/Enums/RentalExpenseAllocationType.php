<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\VehicleRental\Enums;

enum RentalExpenseAllocationType: string
{
    case CompanyCost = 'company_cost';
    case CustomerRecovery = 'customer_recovery';
    case OwnerDeduction = 'owner_deduction';
    case EmployeeReimbursement = 'employee_reimbursement';

    public function label(): string
    {
        return match ($this) {
            self::CompanyCost => 'Company Cost',
            self::CustomerRecovery => 'Customer Recovery (Billable)',
            self::OwnerDeduction => 'Owner Payable Deduction',
            self::EmployeeReimbursement => 'Employee Reimbursement',
        };
    }
}
