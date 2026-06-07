<?php

declare(strict_types=1);

namespace Modules\VehicleService\Services;

use InvalidArgumentException;
use Modules\Invoice\Models\Invoice;
use Modules\Payment\DTOs\CreatePaymentData;
use Modules\Payment\DTOs\PaymentAllocationData;
use Modules\Payment\DTOs\PaymentLineData;
use Modules\Payment\Enums\PaymentDirection;
use Modules\Payment\Enums\PaymentType;
use Modules\VehicleService\Models\VehicleServiceJob;

final class VehicleServicePaymentIntegrationService
{
    public function prepare(
        VehicleServiceJob $job,
        int $invoiceId,
        string $paymentDate,
        string $amount,
        ?int $currencyId = null,
        string $exchangeRate = '1.000000',
        ?string $referenceNumber = null,
        ?int $createdBy = null,
    ): CreatePaymentData {
        if (! $job->invoiceLinks()->where('invoice_id', $invoiceId)->where('status', 'active')->exists()) {
            throw new InvalidArgumentException('Payment invoice is not linked to this service job.');
        }
        $invoice = Invoice::query()
            ->where('tenant_id', $job->tenant_id)
            ->where('id', $invoiceId)
            ->firstOrFail();

        return new CreatePaymentData(
            tenantId: (int) $job->tenant_id,
            paymentType: PaymentType::ServiceReceipt,
            direction: PaymentDirection::Inbound,
            paymentDate: $paymentDate,
            organizationUnitId: $job->organization_unit_id,
            partyType: 'customer',
            partyId: (int) $job->customer_id,
            currencyId: $currencyId ?? $invoice->currency_id,
            exchangeRate: $exchangeRate,
            referenceNumber: $referenceNumber,
            notes: 'Prepared from vehicle service job '.$job->job_number,
            createdBy: $createdBy,
            lines: [new PaymentLineData($amount, referenceNumber: $referenceNumber)],
            allocations: [new PaymentAllocationData(
                invoiceId: $invoiceId,
                allocatedAmount: $amount,
                allocationDate: $paymentDate,
            )],
        );
    }
}
