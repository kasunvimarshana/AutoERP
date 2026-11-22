<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Purchase\Enums;

enum PurchaseReturnType: string
{
    case Referenced = 'referenced';
    case ManualSupplierReturn = 'manual_supplier_return';
}
