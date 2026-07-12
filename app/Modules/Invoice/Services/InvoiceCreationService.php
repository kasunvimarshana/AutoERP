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
use Modules\Invoice\Services\Tax\InvoiceTaxDocumentMapper;
use Modules\Invoice\Validators\InvoiceValidationService;
use Modules\Tax\Services\TaxDocumentIntegrationService;
use Modules\Tax\Services\TaxSnapshotService;

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
        private readonly InvoiceTaxDocumentMapper $taxDocumentMapper,
        private readonly InvoiceIssuanceService $issuance,
        private readonly InvoiceReferenceSnapshotService $snapshots,
        private readonly TaxSnapshotService $taxSnapshots,
        private readonly InvoicePostingPlanService $postingPlans,
    ) {}

    public function create(CreateInvoiceData $data): Invoice
    {
        $this->validator->validateForCreation($data);

        return DB::transaction(function () use ($data): Invoice {
            $sourceLineRows = $this->sourceAllocations->prepareSourceLineAllocations($data, lockRows: true);
            $preparedAdjustments = $this->adjustmentAllocations->prepareAdjustmentAllocations($data, $sourceLineRows);
            $calculation = $this->calculations->calculate(
                $data,
                $this->allocatedAdjustments($preparedAdjustments),
            );

            $headerSnapshot = $this->snapshots->header($data);

            // Creation has exactly one persisted initial state. Requested approved
            // or posted targets are applied below through legal lifecycle commands.
            $invoice = new Invoice();
            $invoice->forceFill([
                'tenant_id' => $data->tenantId,
                'organization_unit_id' => $data->organizationUnitId,
                'invoice_number' => $this->numbers->resolve($data),
                'invoice_type' => $data->invoiceType->value,
                'direction' => $data->direction->value,
                'party_type' => $data->partyType,
                'party_id' => $data->partyId,
                ...$headerSnapshot,
                'invoice_date' => $data->invoiceDate,
                'due_date' => $data->dueDate,
                'currency_id' => $data->currencyId,
                'exchange_rate' => $this->math->normalize($data->exchangeRate),
                'status' => InvoiceStatus::Draft->value,
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
            $invoice->save();

            $this->lines->create($invoice, $data, $calculation, $sourceLineRows);
            $this->sources->createSources(
                $invoice,
                $data,
                $this->sourceAllocations->invoicedAmountBySource($sourceLineRows),
                $this->adjustments->allocatedAdjustmentAmountBySource($preparedAdjustments, $this->math),
            );
            $this->adjustments->createAdjustments($invoice, $preparedAdjustments);
            $this->balances->createBalance($invoice, $calculation->grandTotal);
            if ($data->postingPlan !== null) {
                $this->postingPlans->create($invoice, $data->postingPlan, $data->createdBy);
            }

            $invoice = $invoice->refresh()->load('lines');
            $taxDocument = $this->taxDocumentMapper->map($invoice);
            if ($data->taxCalculation === null) {
                $this->taxDocuments->snapshot($taxDocument);
            } else {
                $this->taxSnapshots->snapshotCalculation($data->taxCalculation, [
                    'tenant_id' => $taxDocument->tenantId,
                    'organization_unit_id' => $taxDocument->organizationUnitId,
                    'source_module' => $taxDocument->sourceModule,
                    'source_type' => $taxDocument->sourceType,
                    'source_id' => $taxDocument->sourceId,
                    'source_number' => $taxDocument->sourceNumber,
                    'source_date' => $taxDocument->sourceDate,
                    'line_ids' => $invoice->lines->mapWithKeys(
                        static fn ($line): array => [(int) $line->line_number => (int) $line->getKey()],
                    )->all(),
                ]);
            }
            $invoice = $this->issuance->advance($invoice, $data->status, $data->createdBy);

            return $invoice->load([
                'lines',
                'sources',
                'sourceLines',
                'adjustments',
                'adjustmentAllocations',
                'balance',
                'postingPlan',
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
