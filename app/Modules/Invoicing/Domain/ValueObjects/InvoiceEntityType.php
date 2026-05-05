<?php

declare(strict_types=1);

namespace Modules\Invoicing\Domain\ValueObjects;

enum InvoiceEntityType: string
{
    case Rental = 'rental';
    case ServiceJob = 'service_job';
    case Reservation = 'reservation';
    case Other = 'other';
}
