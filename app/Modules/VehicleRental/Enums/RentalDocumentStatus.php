<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Enums;

enum RentalDocumentStatus: string
{
    case NotGenerated = 'not_generated';
    case PartiallyGenerated = 'partially_generated';
    case Generated = 'generated';
    case Reversed = 'reversed';
}
