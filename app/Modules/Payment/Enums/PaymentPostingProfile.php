<?php

declare(strict_types=1);

namespace Modules\Payment\Enums;

enum PaymentPostingProfile: string
{
    case CustomerSettlement = 'payment_received';
    case SupplierSettlement = 'payment_made';
    case CustomerAdvance = 'customer_advance';
    case SupplierAdvance = 'supplier_advance';
    case RentalDeposit = 'rental_deposit';
}
