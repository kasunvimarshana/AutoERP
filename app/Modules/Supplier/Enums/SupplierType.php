<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Supplier\Enums;

enum SupplierType: string
{
    case Company = 'company';
    case Individual = 'individual';
    case Government = 'government';
    case Internal = 'internal';
    case Foreign = 'foreign';
    case Other = 'other';
}
