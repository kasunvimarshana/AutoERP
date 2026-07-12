<?php

declare(strict_types=1);

namespace Modules\Payment\Enums;

enum PaymentPostingRole: string
{
    case Cash = 'cash';
    case Bank = 'bank';
    case Receivable = 'receivable';
    case Payable = 'payable';
    case CustomerAdvance = 'customer_advance';
    case SupplierAdvance = 'supplier_advance';
    case CustomerDeposit = 'customer_deposit';
}
