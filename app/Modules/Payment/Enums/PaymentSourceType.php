<?php

declare(strict_types=1);

namespace Modules\Payment\Enums;

enum PaymentSourceType: string
{
    case RentalDepositRequirement = 'rental_deposit_requirement';
    case PaymentRefund = 'payment_refund';
    case PaymentAllocation = 'payment_allocation';
}
