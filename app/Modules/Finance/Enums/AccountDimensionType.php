<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Finance\Enums;

enum AccountDimensionType: string
{
    case OrganizationUnit = 'organization_unit';
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
