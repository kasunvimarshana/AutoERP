<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Enums;

enum RentalExpenseAllocationType: string
{
    case CompanyCost = 'company_cost';
    case CustomerRecovery = 'customer_recovery';
    case OwnerDeduction = 'owner_deduction';
    case EmployeeReimbursement = 'employee_reimbursement';
}
