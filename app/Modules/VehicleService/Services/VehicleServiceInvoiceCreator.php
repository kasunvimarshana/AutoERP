<?php

declare(strict_types=1);

namespace Modules\VehicleService\Services;

use Illuminate\Support\Facades\DB;
use Modules\Core\Services\DecimalMath;
use Modules\Invoice\DTOs\InvoiceLineData;
use Modules\Invoice\Enums\InvoiceStatus;
use Modules\Invoice\Models\Invoice;
use Modules\Invoice\Services\InvoiceCreationService;
use Modules\Invoice\Services\InvoiceStatusService;
use Modules\VehicleService\Enums\VehicleServiceJobStatus;
use Modules\VehicleService\Models\VehicleServiceInvoiceLink;
use Modules\VehicleService\Models\VehicleServiceJob;
use Modules\VehicleService\Services\Concerns\AssertsVehicleServiceExpectedVersion;

final class VehicleServiceInvoiceCreator
{
    use AssertsVehicleServiceExpectedVersion;

    public function __construct(
        private readonly DecimalMath $math,
        private readonly InvoiceCreationService $invoices,
        private readonly InvoiceStatusService $invoiceStatuses,
        private readonly VehicleServiceInvoiceSourceMapper $sources,
        private readonly VehicleServiceStatusService $statuses,
        private readonly VehicleServicePaymentIntegrationService $payments,
    ) {}

    /** @param array<int, string> $lineQuantities */
    public function create(
        VehicleServiceJob $job,
        string $invoiceDate,
        array $lineQuantities = [],
        ?string $dueDate = null,
        ?int $currencyId = null,
        string $exchangeRate = '1.000000',
        ?string $notes = null,
        ?int $createdBy = null,
        ?int $expectedVersion = null,
    ): Invoice {
        return DB::transaction(function () use (
            $job,
            $invoiceDate,
            $lineQuantities,
            $dueDate,
            $currencyId,
            $exchangeRate,
            $notes,
            $createdBy,
            $expectedVersion,
        ): Invoice {
            $job = VehicleServiceJob::query()->lockForUpdate()->findOrFail($job->getKey());
            $this->assertExpectedVersion($job, $expectedVersion);
            $data = $this->sources->toInvoiceData(
                $job,
                $invoiceDate,
                $lineQuantities,
                $dueDate,
                $currencyId,
                $exchangeRate,
                $notes,
                $createdBy,
            );
            $invoice = $this->invoices->create($data);

            VehicleServiceInvoiceLink::query()->create([
                'tenant_id' => $job->tenant_id,
                'organization_unit_id' => $job->organization_unit_id,
                'vehicle_service_job_id' => $job->getKey(),
                'invoice_id' => $invoice->getKey(),
                'source_line_total' => $this->math->sum(array_map(
                    static fn (InvoiceLineData $line): string => (string) $line->lineTotal,
                    $data->lines,
                )),
                'allocated_adjustment_total' => '0.000000',
                'invoice_total' => (string) $invoice->grand_total,
                'status' => 'active',
            ]);

            $invoice = $this->invoiceStatuses->transition($invoice, InvoiceStatus::Approved);
            $invoice = $this->invoiceStatuses->transition($invoice, InvoiceStatus::Posted);

            $statusChanged = false;
            if (! $this->sources->hasRemainingBillableLines($job)
                && $job->status === VehicleServiceJobStatus::Completed) {
                $this->statuses->change($job, VehicleServiceJobStatus::Invoiced, $createdBy);
                $this->payments->syncJobStatus($job->refresh(), $createdBy);
                $statusChanged = true;
            }
            if (! $statusChanged) {
                $this->bumpJobVersion($job);
            }

            return $invoice->refresh()->loadMissing([
                'lines',
                'sources',
                'sourceLines',
                'adjustments',
                'adjustmentAllocations',
                'balance',
            ]);
        });
    }
}
