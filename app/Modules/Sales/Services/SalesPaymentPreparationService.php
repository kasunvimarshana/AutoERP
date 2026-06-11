<?php

declare(strict_types=1);

namespace Modules\Sales\Services;

use Modules\Payment\DTOs\CreatePaymentData;
use Modules\Payment\DTOs\PaymentAllocationData;
use Modules\Payment\DTOs\PaymentLineData;
use Modules\Payment\Enums\PaymentDirection;
use Modules\Payment\Enums\PaymentType;

final class SalesPaymentPreparationService
{
    /**
     * @param  list<PaymentLineData>  $lines
     * @param  list<PaymentAllocationData>  $allocations
     */
    public function prepareCustomerReceipt(
        int $tenantId,
        string $paymentDate,
        string $amount,
        ?int $organizationUnitId = null,
        ?int $customerId = null,
        ?int $currencyId = null,
        string $exchangeRate = '1.000000',
        ?string $referenceNumber = null,
        array $lines = [],
        array $allocations = [],
    ): CreatePaymentData {
        return new CreatePaymentData(
            tenantId: $tenantId,
            paymentType: PaymentType::CustomerReceipt,
            direction: PaymentDirection::Inbound,
            paymentDate: $paymentDate,
            organizationUnitId: $organizationUnitId,
            partyType: 'customer',
            partyId: $customerId,
            currencyId: $currencyId,
            exchangeRate: $exchangeRate,
            referenceNumber: $referenceNumber,
            lines: $lines === [] ? [new PaymentLineData($amount, referenceNumber: $referenceNumber)] : $lines,
            allocations: $allocations,
        );
    }
}
