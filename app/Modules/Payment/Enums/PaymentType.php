<?php

declare(strict_types=1);

namespace Modules\Payment\Enums;

enum PaymentType: string
{
    case SupplierPayment = 'supplier_payment';
    case CustomerReceipt = 'customer_receipt';
    case ServiceReceipt = 'service_receipt';
    case Advance = 'advance';
    case Refund = 'refund';
    case Manual = 'manual';
}