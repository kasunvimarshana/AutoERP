<?php

declare(strict_types=1);

namespace Modules\Payment\Enums;

enum PaymentPostingProfile: string
{
    case CustomerSettlement = 'customer_receipt';
    case SupplierSettlement = 'supplier_payment';
    case CustomerAdvance = 'customer_advance';
    case SupplierAdvance = 'supplier_advance';
}