<?php

declare(strict_types=1);

namespace Modules\Sales\Services;

use Illuminate\Support\Facades\DB;
use Modules\Invoice\DTOs\InvoiceCalculationResult;
use Modules\Invoice\Models\Invoice;
use Modules\Invoice\Services\InvoiceCreationService;
use Modules\Sales\DTOs\CreateSalesInvoiceData;
use Modules\Sales\DTOs\PreparedSalesInvoiceData;
use Modules\Sales\Models\SalesInvoiceLink;

final class SalesInvoiceIntegrationService
{
    public function __construct(
        private readonly InvoiceCreationService $invoices,
        private readonly SalesInvoiceDtoFactory $invoiceData,
        private readonly SalesInvoiceQuantityUpdater $quantities,
    ) {}

    public function createCustomerInvoice(CreateSalesInvoiceData $data): Invoice
    {
        return DB::transaction(function () use ($data): Invoice {
            $prepared = $this->invoiceData->prepare($data, lockSources: true);
            $invoice = $this->invoices->create($prepared->invoiceData);

            $this->createSalesLinks($data, $prepared, $invoice);
            $this->quantities->apply($prepared->lineQuantities, $prepared->deliveries);

            return $invoice;
        });
    }

    public function previewCustomerInvoice(CreateSalesInvoiceData $data): InvoiceCalculationResult
    {
        return $this->invoices->preview($this->invoiceData->prepare($data)->invoiceData);
    }

    private function createSalesLinks(
        CreateSalesInvoiceData $data,
        PreparedSalesInvoiceData $prepared,
        Invoice $invoice,
    ): void {
        foreach ($prepared->sourceTotals as $sourceKey => $totals) {
            [$sourceType, $sourceId] = explode(':', $sourceKey, 2);
            SalesInvoiceLink::query()->create([
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
