<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\VehicleRental\Enums;

enum BillingFinalizationMode: string
{
    case SupplementaryInvoice = 'supplementary_invoice';
    case ConsolidatedRevision = 'consolidated_revision';
}
