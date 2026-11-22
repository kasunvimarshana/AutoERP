<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Customer\Enums;

enum CustomerType: string
{
    case Company = 'company';
    case Individual = 'individual';
    case Government = 'government';
    case Internal = 'internal';
    case Foreign = 'foreign';
    case Retail = 'retail';
    case Wholesale = 'wholesale';
    case Corporate = 'corporate';
    case Other = 'other';
}
