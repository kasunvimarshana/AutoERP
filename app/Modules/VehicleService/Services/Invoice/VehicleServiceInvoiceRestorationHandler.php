<?php

declare(strict_types=1);

namespace Modules\VehicleService\Services\Invoice;

use Modules\Invoice\Contracts\InvoiceSourceRestorationHandlerInterface;
use Modules\Invoice\Data\InvoiceSourceRestorationContext;
use Modules\VehicleService\Models\VehicleServiceInvoiceLink;
use Modules\VehicleService\Models\VehicleServiceJob;
use Modules\VehicleService\Services\VehicleServiceStatusService;

final class VehicleServiceInvoiceRestorationHandler implements InvoiceSourceRestorationHandlerInterface
{
    public function __construct(private readonly VehicleServiceStatusService $statuses) {}

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
        $jobIds = VehicleServiceInvoiceLink::query()
            ->where('tenant_id', $context->tenantId)
            ->where('invoice_id', $context->invoiceId)
            ->pluck('vehicle_service_job_id');

        VehicleServiceInvoiceLink::query()
            ->where('tenant_id', $context->tenantId)
            ->where('invoice_id', $context->invoiceId)
            ->where('status', 'active')
            ->update(['status' => $context->linkStatus()]);

        $jobs = VehicleServiceJob::query()
            ->forContext($context->tenantId, $context->organizationUnitId)
            ->whereKey($jobIds)->orderBy('id')->get();
        foreach ($jobs as $job) {
            $this->statuses->restoreCompletedAfterBillingReversal(
                $job,
                $context->actorId,
                trim('Billing cleared after invoice '.$context->terminalStatus->value.'. '.$context->reason),
            );
        }
    }
}
