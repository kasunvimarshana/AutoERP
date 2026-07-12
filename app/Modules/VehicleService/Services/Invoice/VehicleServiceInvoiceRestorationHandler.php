<?php

declare(strict_types=1);

namespace Modules\VehicleService\Services\Invoice;

use Modules\Invoice\Contracts\InvoiceSourceRestorationHandlerInterface;
use Modules\Invoice\Data\InvoiceSourceRestorationContext;
use Modules\VehicleService\Models\VehicleServiceInvoiceLink;

final class VehicleServiceInvoiceRestorationHandler implements InvoiceSourceRestorationHandlerInterface
{
    public function supports(InvoiceSourceRestorationContext $context): bool
    {
        return VehicleServiceInvoiceLink::query()
            ->where('tenant_id', $context->tenantId)
            ->where('invoice_id', $context->invoiceId)
            ->where('status', 'active')
            ->exists();
    }

    public function restore(InvoiceSourceRestorationContext $context): void
    {
        VehicleServiceInvoiceLink::query()
            ->where('tenant_id', $context->tenantId)
            ->where('invoice_id', $context->invoiceId)
            ->where('status', 'active')
            ->update(['status' => $context->linkStatus()]);
    }
}
