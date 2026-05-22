<?php

declare(strict_types=1);

namespace Modules\VehicleService\Domain\Enums;

enum VehicleServiceInvoiceStatus: string
{
    case NotInvoiced = 'not_invoiced';
    case PartiallyInvoiced = 'partially_invoiced';
    case Closed = 'closed';
}
