<?php

declare(strict_types=1);

namespace Modules\Finance\Enums;

enum AccountDimensionType: string
{
    case Department = 'department';
    case Project = 'project';
    case CostCenter = 'cost_center';
    case Branch = 'branch';
    case Customer = 'customer';
    case Supplier = 'supplier';
    case Employee = 'employee';
    case Vehicle = 'vehicle';
    case Custom = 'custom';
}
