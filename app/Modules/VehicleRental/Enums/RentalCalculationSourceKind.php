<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Enums;

enum RentalCalculationSourceKind: string
{
    case UsageContext = 'usage_context';
    case ExpenseAllocation = 'expense_allocation';
}
