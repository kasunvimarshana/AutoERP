<?php

declare(strict_types=1);

namespace Modules\Sales\Services;

use InvalidArgumentException;
use Modules\Payment\DTOs\CreatePaymentData;
use Modules\Payment\DTOs\PaymentAllocationData;
use Modules\Payment\DTOs\PaymentLineData;
use Modules\Payment\Enums\PaymentDirection;
use Modules\Payment\Enums\PaymentType;

final class SalesPaymentPreparationService
{
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
        ?int $createdBy = null,
        ?string $notes = null,
    ): CreatePaymentData {
        if ($customerId === null) {
            throw new InvalidArgumentException('Customer receipt requires a customer.');
        }
        if ($lines === []) {
            throw new InvalidArgumentException('Customer receipt requires at least one payment method line.');
        }
        foreach ($lines as $line) {
            if (! $line instanceof PaymentLineData) {
                throw new InvalidArgumentException('Customer receipt lines are invalid.');
            }
        }
        foreach ($allocations as $allocation) {
            if (! $allocation instanceof PaymentAllocationData) {
                throw new InvalidArgumentException('Customer receipt allocations are invalid.');
            }
        }

        return new CreatePaymentData(
            tenantId: $tenantId,
            paymentType: PaymentType::CustomerReceipt,
            direction: PaymentDirection::Inbound,
            paymentDate: $paymentDate,
            organizationUnitId: $organizationUnitId,
            partyType: 'customer',
            partyId: $customerId,
            sourceType: 'sales',
            currencyId: $currencyId,
            exchangeRate: $exchangeRate,
            referenceNumber: $referenceNumber,
            notes: $notes,
            createdBy: $createdBy,
            lines: $lines,
            allocations: $allocations,
        );
    }
}
