<?php

declare(strict_types=1);

namespace Modules\Purchase\Services;

use Modules\Payment\DTOs\CreatePaymentData;
use Modules\Payment\DTOs\PaymentLineData;
use Modules\Payment\Enums\PaymentDirection;
use Modules\Payment\Enums\PaymentType;

final class PurchasePaymentIntegrationService
{
    /**
     * Prepare a supplier payment DTO. Payment owns persistence and invoice settlement.
     *
     * @param  list<PaymentLineData>  $lines
     */
    public function prepareSupplierPayment(
        int $tenantId,
        string $paymentDate,
        string $amount,
        ?int $organizationUnitId = null,
        ?string $supplierType = null,
        ?int $supplierId = null,
        ?int $currencyId = null,
        string $exchangeRate = '1.000000',
        ?string $referenceNumber = null,
        array $lines = [],
    ): CreatePaymentData {
        return new CreatePaymentData(
            tenantId: $tenantId,
            paymentType: PaymentType::SupplierPayment,
            direction: PaymentDirection::Outbound,
            paymentDate: $paymentDate,
            organizationUnitId: $organizationUnitId,
            partyType: $supplierType,
            partyId: $supplierId,
            currencyId: $currencyId,
            exchangeRate: $exchangeRate,
            referenceNumber: $referenceNumber,
            lines: $lines === [] ? [new PaymentLineData($amount, referenceNumber: $referenceNumber)] : $lines,
        );
    }
}
