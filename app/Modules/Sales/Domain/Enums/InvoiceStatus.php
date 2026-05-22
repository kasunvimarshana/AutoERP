<?php

declare(strict_types=1);

namespace Modules\Sales\Domain\Enums;

enum InvoiceStatus: string
{
    case NotInvoiced = 'not_invoiced';
    case PartiallyInvoiced = 'partially_invoiced';
    case Closed = 'closed';
}
