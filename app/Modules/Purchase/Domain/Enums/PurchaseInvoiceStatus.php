<?php

declare(strict_types=1);

namespace Modules\Purchase\Domain\Enums;

enum PurchaseInvoiceStatus: string
{
    case NotInvoiced = 'not_invoiced';
    case PartiallyInvoiced = 'partially_invoiced';
    case Closed = 'closed';
}
