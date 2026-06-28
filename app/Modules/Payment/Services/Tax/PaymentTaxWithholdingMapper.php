<?php

declare(strict_types=1);

namespace Modules\Payment\Services\Tax;

use BackedEnum;
use Modules\Invoice\Models\Invoice;
use Modules\Invoice\Services\Tax\InvoiceTaxDocumentMapper;
use Modules\Payment\Models\Payment;
use Modules\Tax\Data\TaxPaymentWithholdingAllocationData;
use Modules\Tax\Data\TaxPaymentWithholdingData;

final class PaymentTaxWithholdingMapper
{
    public function __construct(
        private readonly InvoiceTaxDocumentMapper $invoices,
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

            $invoice = Invoice::query()
                ->where('tenant_id', (int) $payment->tenant_id)
                ->where('organization_unit_id', $allocation->organization_unit_id)
                ->findOrFail((int) $allocation->invoice_id);
            $allocations[] = new TaxPaymentWithholdingAllocationData(
                invoice: $this->invoices->map($invoice),
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
