<?php

declare(strict_types=1);

namespace Modules\Invoice\Services;

use Illuminate\Support\Facades\DB;
use Modules\Core\Services\DecimalMath;
use Modules\Invoice\DTOs\CreateInvoiceData;
use Modules\Invoice\DTOs\InvoiceAdjustmentData;
use Modules\Invoice\DTOs\InvoiceCalculationResult;
use Modules\Invoice\Enums\InvoiceStatus;
use Modules\Invoice\Models\Invoice;
use Modules\Invoice\Validators\InvoiceValidationService;
use Modules\Tax\Services\TaxDocumentIntegrationService;

final class InvoiceCreationService
{
    public function __construct(
        private readonly DecimalMath $math,
        private readonly InvoiceValidationService $validator,
        private readonly InvoiceNumberService $numbers,
        private readonly InvoiceCalculationService $calculations,
        private readonly InvoiceSourceAllocationService $sourceAllocations,
        private readonly InvoiceAdjustmentAllocationService $adjustmentAllocations,
        private readonly InvoiceLineService $lines,
        private readonly InvoiceSourceService $sources,
        private readonly InvoiceAdjustmentService $adjustments,
        private readonly InvoiceBalanceService $balances,
        private readonly TaxDocumentIntegrationService $taxDocuments,
    ) {}

    public function create(CreateInvoiceData $data): Invoice
    {
        $this->validator->validateForCreation($data);

        return DB::transaction(function () use ($data): Invoice {
            $sourceLineRows = $this->sourceAllocations->prepareSourceLineAllocations($data);
            $preparedAdjustments = $this->adjustmentAllocations->prepareAdjustmentAllocations($data, $sourceLineRows);
            $calculation = $this->calculations->calculate(
                $data,
                $this->allocatedAdjustments($preparedAdjustments),
            );

            $invoice = Invoice::query()->create([
                'tenant_id' => $data->tenantId,
                'organization_unit_id' => $data->organizationUnitId,
                'invoice_number' => $this->numbers->resolve($data),
                'invoice_type' => $data->invoiceType->value,
                'direction' => $data->direction->value,
                'party_type' => $data->partyType,
                'party_id' => $data->partyId,
                'invoice_date' => $data->invoiceDate,
                'due_date' => $data->dueDate,
                'currency_id' => $data->currencyId,
                'exchange_rate' => $this->math->normalize($data->exchangeRate),
                'status' => $data->status->value,
                'subtotal' => $calculation->subtotal,
                'discount_total' => $calculation->discountTotal,
                'tax_total' => $calculation->taxTotal,
                'charge_total' => $calculation->chargeTotal,
                'adjustment_total' => $calculation->adjustmentTotal,
                'grand_total' => $calculation->grandTotal,
                'paid_total' => '0.000000',
                'credit_total' => '0.000000',
                'balance_due' => $calculation->grandTotal,
                'notes' => $data->notes,
                'created_by' => $data->createdBy,
            ]);

            $this->lines->create($invoice, $data, $calculation, $sourceLineRows);

            $this->sources->createSources(
                $invoice,
                $data,
                $this->sourceAllocations->invoicedAmountBySource($sourceLineRows),
                $this->adjustments->allocatedAdjustmentAmountBySource($preparedAdjustments, $this->math),
            );
            $this->adjustments->createAdjustments($invoice, $preparedAdjustments);
            $this->balances->createBalance($invoice, $calculation->grandTotal);
            $this->taxDocuments->snapshotInvoice($invoice->refresh()->load('lines'));
            if ($data->status === InvoiceStatus::Posted) {
                $this->taxDocuments->postInvoice($invoice->refresh());
            }

            return $invoice->load([
                'lines',
                'sources',
                'sourceLines',
                'adjustments',
                'adjustmentAllocations',
                'balance',
            ]);
        });
    }

    public function preview(CreateInvoiceData $data): InvoiceCalculationResult
    {
        $this->validator->validateForCreation($data);
        $sourceLineRows = $this->sourceAllocations->prepareSourceLineAllocations($data);
        $preparedAdjustments = $this->adjustmentAllocations->prepareAdjustmentAllocations($data, $sourceLineRows);

        return $this->calculations->calculate(
            $data,
            $this->allocatedAdjustments($preparedAdjustments),
        );
    }

    /**
     * @param  list<array{adjustment: InvoiceAdjustmentData, allocation: array<string, mixed>|null}>  $preparedAdjustments
     * @return list<InvoiceAdjustmentData>
     */
    private function allocatedAdjustments(array $preparedAdjustments): array
    {
        return array_map(
            static fn (array $prepared): InvoiceAdjustmentData => $prepared['adjustment'],
            $preparedAdjustments,
        );
    }
}
