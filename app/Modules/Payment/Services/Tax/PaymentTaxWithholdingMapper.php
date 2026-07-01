<?php

declare(strict_types=1);

namespace Modules\Payment\Services\Tax;

use BackedEnum;
use Modules\Invoice\Contracts\InvoiceTaxDocumentProviderInterface;
use Modules\Payment\Models\Payment;
use Modules\Tax\Data\TaxPaymentWithholdingAllocationData;
use Modules\Tax\Data\TaxPaymentWithholdingData;

final class PaymentTaxWithholdingMapper
{
    public function __construct(
        private readonly InvoiceTaxDocumentProviderInterface $invoices,
    ) {}

    public function map(Payment $payment): TaxPaymentWithholdingData
    {
        $payment->loadMissing('allocations');
        $allocations = [];
        foreach ($payment->allocations as $allocation) {
            $status = $allocation->status instanceof BackedEnum
                ? $allocation->status->value
                : (string) $allocation->status;
            if ($status !== 'active') {
                continue;
            }

            $allocations[] = new TaxPaymentWithholdingAllocationData(
                invoice: $this->invoices->taxableDocument(
                    (int) $payment->tenant_id,
                    $allocation->organization_unit_id === null ? null : (int) $allocation->organization_unit_id,
                    (int) $allocation->invoice_id,
                ),
                allocatedAmount: (string) $allocation->allocated_amount,
                invoiceTotal: (string) $allocation->invoice_total,
            );
        }

        return new TaxPaymentWithholdingData(
            tenantId: (int) $payment->tenant_id,
            organizationUnitId: $payment->organization_unit_id === null ? null : (int) $payment->organization_unit_id,
            paymentId: (int) $payment->getKey(),
            paymentNumber: (string) $payment->payment_number,
            paymentDate: $payment->payment_date->toDateString(),
            allocations: $allocations,
        );
    }
}
