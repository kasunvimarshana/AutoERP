<?php

declare(strict_types=1);

namespace Modules\VehicleService\Services\Invoice;

use Modules\Invoice\Contracts\InvoiceSourceCancellationHandlerInterface;
use Modules\Invoice\Data\InvoiceSourceCancellationContext;
use Modules\VehicleService\Models\VehicleServiceInvoiceLink;

final class VehicleServiceInvoiceCancellationHandler implements InvoiceSourceCancellationHandlerInterface
{
    public function supports(InvoiceSourceCancellationContext $context): bool
    {
        return VehicleServiceInvoiceLink::query()
            ->where('tenant_id', $context->tenantId)
            ->where('invoice_id', $context->invoiceId)
            ->where('status', 'active')
            ->exists();
    }

    public function restore(InvoiceSourceCancellationContext $context): void
    {
        VehicleServiceInvoiceLink::query()
            ->where('tenant_id', $context->tenantId)
            ->where('invoice_id', $context->invoiceId)
            ->where('status', 'active')
            ->update(['status' => 'cancelled']);
    }
}
