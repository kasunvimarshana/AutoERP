<?php

declare(strict_types=1);

namespace Modules\Purchase\Services;

use Illuminate\Support\Facades\DB;
use Modules\Core\Services\DecimalMath;
use Modules\Invoice\DTOs\InvoiceCalculationResult;
use Modules\Invoice\Models\Invoice;
use Modules\Invoice\Services\InvoiceCreationService;
use Modules\Purchase\DTOs\CreatePurchaseInvoiceData;
use Modules\Purchase\DTOs\PreparedPurchaseInvoiceData;
use Modules\Purchase\Models\PurchaseInvoiceLink;
use Modules\Purchase\Validators\PurchaseValidationService;

final class PurchaseInvoiceIntegrationService
{
    public function __construct(
        private readonly DecimalMath $math,
        private readonly InvoiceCreationService $invoices,
        private readonly PurchaseInvoiceDtoFactory $invoiceData,
        private readonly PurchaseInvoiceQuantityUpdater $quantities,
        private readonly PurchaseAdjustmentAllocationService $adjustmentAllocations,
        private readonly PurchaseValidationService $validator,
    ) {}

    public function createSupplierInvoice(CreatePurchaseInvoiceData $data): Invoice
    {
        return DB::transaction(function () use ($data): Invoice {
            $this->validateHeaderReferences($data);
            $prepared = $this->invoiceData->prepare($data, lockSources: true);
            $invoice = $this->invoices->create($prepared->invoiceData);

            $this->createPurchaseLinks($data, $prepared, $invoice);
            $this->adjustmentAllocations->recordInvoiceAllocationsForInvoice($invoice, $prepared);
            $this->quantities->apply($prepared->lineQuantities, $prepared->goodsReceipts);

            return $invoice;
        });
    }

    public function previewSupplierInvoice(CreatePurchaseInvoiceData $data): InvoiceCalculationResult
    {
        $this->validateHeaderReferences($data);

        return $this->invoices->preview($this->invoiceData->prepare($data)->invoiceData);
    }

    private function validateHeaderReferences(CreatePurchaseInvoiceData $data): void
    {
        if ($data->currencyId !== null) {
            $this->validator->currency($data->tenantId, $data->organizationUnitId, $data->currencyId, 'currency_id');
        }
    }

    private function createPurchaseLinks(
        CreatePurchaseInvoiceData $data,
        PreparedPurchaseInvoiceData $prepared,
        Invoice $invoice,
    ): void {
        foreach ($prepared->sourceTotals as $sourceKey => $totals) {
            [$sourceType, $sourceId] = explode(':', $sourceKey, 2);
            $invoiceSource = $invoice->sources
                ->first(fn ($source): bool => $source->source_type === $sourceType && (int) $source->source_id === (int) $sourceId);
            $sourceLineTotal = $invoiceSource === null
                ? $totals['line_total']
                : (string) $invoiceSource->invoiced_amount;
            $allocatedAdjustmentTotal = $invoiceSource === null
                ? $totals['adjustment_total']
                : (string) $invoiceSource->allocated_adjustment_amount;

            PurchaseInvoiceLink::query()->create([
                'tenant_id' => $data->tenantId,
                'organization_unit_id' => $data->organizationUnitId,
                'invoice_id' => $invoice->getKey(),
                'source_type' => $sourceType,
                'source_id' => (int) $sourceId,
                'source_line_total' => $sourceLineTotal,
                'allocated_adjustment_total' => $allocatedAdjustmentTotal,
                'invoice_total' => $this->sourceInvoiceTotal($sourceLineTotal, $allocatedAdjustmentTotal),
                'status' => 'active',
            ]);
        }
    }

    private function sourceInvoiceTotal(string $lineTotal, string $allocatedAdjustmentTotal): string
    {
        return $this->math->add($lineTotal, $allocatedAdjustmentTotal);
    }
}
