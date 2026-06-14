<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Enums;

enum RentalExpenseFinancialTreatment: string
{
    case CompanyBorne = 'company_borne';
    case CustomerBillable = 'customer_billable';
    case SupplierRecoverable = 'supplier_recoverable';
    case EmployeeReimbursable = 'employee_reimbursable';
    case OwnerPayable = 'owner_payable';
}
