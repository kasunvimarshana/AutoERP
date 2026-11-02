<?php

declare(strict_types=1);

namespace Modules\Invoice\Services;

use Modules\Invoice\DTOs\CreateInvoiceData;
use Modules\Invoice\Models\Invoice;
use Modules\Invoice\Models\InvoiceSource;

final class InvoiceSourceService
{
    public function __construct(private readonly InvoiceSourceAllocationService $sourceAllocations) {}

    /**
     * @param  array<string, string>  $invoicedAmountBySource
     * @param  array<string, string>  $allocatedAdjustmentBySource
     */
    public function createSources(
        Invoice $invoice,
        CreateInvoiceData $data,
        array $invoicedAmountBySource,
        array $allocatedAdjustmentBySource,
    ): void {
        foreach ($data->sources as $source) {
            $key = $this->sourceAllocations->sourceKey($source->sourceType, $source->sourceId);

            InvoiceSource::query()->create([
                'tenant_id' => $data->tenantId,
                'organization_unit_id' => $data->organizationUnitId,
                'invoice_id' => $invoice->getKey(),
                'source_type' => $source->sourceType,
                'source_id' => $source->sourceId,
                'source_document_number' => $source->sourceDocumentNumber,
                'source_document_date' => $source->sourceDocumentDate,
                'source_subtotal' => $source->sourceSubtotal,
                'source_adjustment_total' => $source->sourceAdjustmentTotal,
                'source_grand_total' => $source->sourceGrandTotal,
                'invoiced_amount' => $invoicedAmountBySource[$key] ?? $source->invoicedAmount,
                'allocated_adjustment_amount' => $allocatedAdjustmentBySource[$key] ?? $source->allocatedAdjustmentAmount,
            ]);
        }
    }
}
