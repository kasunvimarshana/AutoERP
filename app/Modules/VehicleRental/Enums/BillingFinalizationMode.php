<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Enums;

enum BillingFinalizationMode: string
{
    case SupplementaryInvoice = 'supplementary_invoice';
    case ConsolidatedRevision = 'consolidated_revision';
}
