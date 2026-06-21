<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Enums;

enum RentalDepositLinkType: string
{
    case Receipt = 'receipt';
    case Refund = 'refund';
    case AppliedToInvoice = 'applied_to_invoice';
    case Forfeiture = 'forfeiture';
    case Reversal = 'reversal';
}
