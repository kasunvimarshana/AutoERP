<?php

declare(strict_types=1);

namespace Modules\Purchase\Services;

use Illuminate\Support\Facades\DB;
use Modules\Invoice\DTOs\InvoiceCalculationResult;
use Modules\Invoice\Models\Invoice;
use Modules\Invoice\Services\InvoiceCreationService;
use Modules\Purchase\DTOs\CreatePurchaseInvoiceData;
use Modules\Purchase\DTOs\PreparedPurchaseInvoiceData;
use Modules\Purchase\Models\PurchaseInvoiceLink;

final class PurchaseInvoiceIntegrationService
{
    public function __construct(
        private readonly InvoiceCreationService $invoices,
        private readonly PurchaseInvoiceDtoFactory $invoiceData,
        private readonly PurchaseInvoiceQuantityUpdater $quantities,
    ) {}

    public function createSupplierInvoice(CreatePurchaseInvoiceData $data): Invoice
    {
        return DB::transaction(function () use ($data): Invoice {
            $prepared = $this->invoiceData->prepare($data, lockSources: true);
            $invoice = $this->invoices->create($prepared->invoiceData);

            $this->createPurchaseLinks($data, $prepared, $invoice);
            $this->quantities->apply($prepared->lineQuantities, $prepared->goodsReceipts);

            return $invoice;
        });
    }

    public function previewSupplierInvoice(CreatePurchaseInvoiceData $data): InvoiceCalculationResult
    {
        return $this->invoices->preview($this->invoiceData->prepare($data)->invoiceData);
    }

    private function createPurchaseLinks(
        CreatePurchaseInvoiceData $data,
        PreparedPurchaseInvoiceData $prepared,
        Invoice $invoice,
    ): void {
        foreach ($prepared->sourceTotals as $sourceKey => $totals) {
            [$sourceType, $sourceId] = explode(':', $sourceKey, 2);
            PurchaseInvoiceLink::query()->create([
                'tenant_id' => $data->tenantId,
                'organization_unit_id' => $data->organizationUnitId,
                'invoice_id' => $invoice->getKey(),
                'source_type' => $sourceType,
                'source_id' => (int) $sourceId,
                'source_line_total' => $totals['line_total'],
                'allocated_adjustment_total' => $totals['adjustment_total'],
                'invoice_total' => (string) $invoice->grand_total,
                'status' => 'active',
            ]);
        }
    }
}
