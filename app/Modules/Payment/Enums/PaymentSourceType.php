<?php

declare(strict_types=1);

namespace Modules\Payment\Enums;

enum PaymentSourceType: string
{
    case RentalDepositRequirement = 'rental_deposit_requirement';
    case RentalAgreementDeposit = 'vehicle_rental_customer_agreement_deposit';
    case PaymentRefund = 'payment_refund';
    case PaymentAllocation = 'payment_allocation';

    public function isRentalDeposit(): bool
    {
        return in_array($this, [self::RentalDepositRequirement, self::RentalAgreementDeposit], true);
    }
}
