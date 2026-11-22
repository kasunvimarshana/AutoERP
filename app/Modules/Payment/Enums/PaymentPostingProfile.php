<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Payment\Enums;

enum PaymentPostingProfile: string
{
    case CustomerSettlement = 'customer_receipt';
    case SupplierSettlement = 'supplier_payment';
    case CustomerAdvance = 'customer_advance';
    case SupplierAdvance = 'supplier_advance';
    case RentalDeposit = 'rental_deposit';
}
