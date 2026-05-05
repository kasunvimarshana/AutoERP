<?php

declare(strict_types=1);

namespace Modules\Invoicing\Domain\ValueObjects;

enum InvoiceType: string
{
    case Rental = 'rental';
    case Service = 'service';
    case Mixed = 'mixed';
    case Other = 'other';
}
