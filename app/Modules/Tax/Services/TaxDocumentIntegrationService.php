<?php

declare(strict_types=1);

namespace Modules\Tax\Services;

use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\Finance\DTOs\PostingSourceData;
use Modules\Tax\Data\TaxPaymentWithholdingData;
use Modules\Tax\Data\TaxableDocumentData;
use Modules\Tax\DTOs\TaxAmountData;
use Modules\Tax\DTOs\TaxCalculationData;
use Modules\Tax\DTOs\TaxCalculationLineData;
use Modules\Tax\DTOs\TaxPostingContext;
use Modules\Tax\Models\TaxDocumentSnapshot;
use Modules\Tax\Models\TaxTransaction;

final class TaxDocumentIntegrationService
{
    public function __construct(
        private readonly TaxCalculationService $calculator,
        private readonly TaxSnapshotService $snapshots,
        private readonly TaxPostingContextService $postingContexts,
        private readonly DecimalMath $math,
    ) {}

    /**
     * @return list<TaxDocumentSnapshot>
     */
    public function snapshot(TaxableDocumentData $document): array
    {
        $lineIds = [];
        $lines = [];
        foreach ($document->lines as $line) {
            $lineIds[$line->lineNumber] = $line->lineId;
            $lines[] = new TaxCalculationLineData(
                lineNumber: $line->lineNumber,
                quantity: $line->quantity,
                unitPrice: $line->unitPrice,
                itemId: $line->itemId,
                taxGroupId: $line->taxGroupId,
                discountBeforeTax: $line->discountBeforeTax,
                chargeAfterTax: $line->chargeAfterTax,
            );
        }

        $calculation = $this->calculator->calculate(new TaxCalculationData(
            tenantId: $document->tenantId,
            documentType: $document->documentType,
            documentDate: $document->sourceDate,
            organizationUnitId: $document->organizationUnitId,
            customerId: $document->partyType === 'customer' ? $document->partyId : null,
            supplierId: $document->partyType === 'supplier' ? $document->partyId : null,
            lines: $lines,
        ));

        return $this->snapshots->snapshotCalculation($calculation, [
            'tenant_id' => $document->tenantId,
            'organization_unit_id' => $document->organizationUnitId,
            'source_module' => $document->sourceModule,
            'source_type' => $document->sourceType,
            'source_id' => $document->sourceId,
            'source_number' => $document->sourceNumber,
            'source_date' => $document->sourceDate,
            'line_ids' => $lineIds,
        ]);
    }

    public function post(TaxableDocumentData $document): void
    {
        $this->postSource(
            sourceType: $document->sourceType,
            sourceId: $document->sourceId,
            tenantId: $document->tenantId,
            snapshot: fn (): array => $this->snapshot($document),
            attributes: [
                'transaction_date' => $document->transactionDate,
                'party_type' => $document->partyType,
                'party_id' => $document->partyId,
            ],
        );
    }

    /**
     * @return list<TaxDocumentSnapshot>
     */
    public function reverse(
        TaxableDocumentData $document,
        string $reversalSourceType,
        string $reversalLineType,
    ): array {
        if (TaxDocumentSnapshot::query()
            ->where('tenant_id', $document->tenantId)
            ->where('source_type', $reversalSourceType)
            ->where('source_id', $document->sourceId)
            ->exists()) {
            return [];
        }

        $originals = TaxDocumentSnapshot::query()
            ->where('tenant_id', $document->tenantId)
            ->where('source_type', $document->sourceType)
            ->where('source_id', $document->sourceId)
            ->where('posted', true)
            ->orderBy('sequence')
            ->get();

        $created = [];
        foreach ($originals as $original) {
            $snapshot = $this->snapshots->createReversalSnapshot(
                original: $original,
                source: [
                    'tenant_id' => $document->tenantId,
                    'organization_unit_id' => $document->organizationUnitId,
                    'source_module' => $document->sourceModule,
                    'source_type' => $reversalSourceType,
                    'source_id' => $document->sourceId,
                    'source_number' => $document->sourceNumber,
                    'source_date' => $document->transactionDate,
                ],
                line: [
                    'line_type' => $reversalLineType,
                    'line_id' => $original->line_id,
                    'line_number' => is_array($original->metadata)
                        ? ($original->metadata['line_number'] ?? null)
                        : null,
                ],
                ratio: '1.000000000000',
                metadata: [
                    'reversed_source_type' => $document->sourceType,
                    'reversed_source_id' => $document->sourceId,
                ],
            );

            $this->snapshots->recordTransaction($snapshot, [
                'transaction_date' => $document->transactionDate,
                'party_type' => $document->partyType,
                'party_id' => $document->partyId,
                'metadata' => $snapshot->metadata,
            ]);
            $created[] = $snapshot;
        }

        $this->snapshots->markPosted($document->tenantId, $reversalSourceType, $document->sourceId);

        return $created;
    }

    public function withholdingPostingContextForDocument(
        TaxableDocumentData $document,
        string $postingDate,
        string $postingProfileCode,
        string $counterpartyProfileKey,
        string $counterpartyLineName,
    ): TaxPostingContext {
        $this->ensureSnapshot($document);
        $taxLines = TaxDocumentSnapshot::query()
            ->where('tenant_id', $document->tenantId)
            ->where('source_type', $document->sourceType)
            ->where('source_id', $document->sourceId)
            ->where('is_withholding', true)
            ->get()
            ->map(fn (TaxDocumentSnapshot $snapshot): TaxAmountData => $this->amountFromSnapshot($snapshot))
            ->values()
            ->all();

        return $this->postingContexts->build(
            source: new PostingSourceData(
                sourceType: $document->sourceType,
                sourceId: $document->sourceId,
                tenantId: $document->tenantId,
                organizationUnitId: $document->organizationUnitId,
                sourceModule: $document->sourceModule,
                sourceNumber: $document->sourceNumber,
                sourceDate: $document->sourceDate,
            ),
            postingDate: $postingDate,
            taxLines: $taxLines,
            postingProfileCode: $postingProfileCode,
            counterpartyProfileKey: $counterpartyProfileKey,
            counterpartyLineName: $counterpartyLineName,
            description: 'Withholding tax '.$document->sourceNumber,
        );
    }

    public function withholdingPostingContextForPayment(
        TaxPaymentWithholdingData $payment,
        string $postingDate,
        string $postingProfileCode,
        string $counterpartyProfileKey,
        string $counterpartyLineName,
    ): TaxPostingContext {
        $taxLines = [];
        foreach ($payment->allocations as $allocation) {
            if ($this->math->isZero($allocation->invoiceTotal)) {
                throw new InvalidArgumentException('Cannot calculate payment withholding tax against a zero invoice total.');
            }

            $this->ensureSnapshot($allocation->invoice);
            $ratio = $this->math->div($allocation->allocatedAmount, $allocation->invoiceTotal, 12);
            foreach (TaxDocumentSnapshot::query()
                ->where('tenant_id', $payment->tenantId)
                ->where('source_type', $allocation->invoice->sourceType)
                ->where('source_id', $allocation->invoice->sourceId)
                ->where('is_withholding', true)
                ->get() as $snapshot) {
                $taxLines[] = $this->amountFromSnapshot($snapshot, $ratio);
            }
        }

        return $this->postingContexts->build(
            source: new PostingSourceData(
                sourceType: 'payment',
                sourceId: $payment->paymentId,
                tenantId: $payment->tenantId,
                organizationUnitId: $payment->organizationUnitId,
                sourceModule: 'payment',
                sourceNumber: $payment->paymentNumber,
                sourceDate: $payment->paymentDate,
            ),
            postingDate: $postingDate,
            taxLines: $taxLines,
            postingProfileCode: $postingProfileCode,
            counterpartyProfileKey: $counterpartyProfileKey,
            counterpartyLineName: $counterpartyLineName,
            description: 'Withholding tax '.$payment->paymentNumber,
        );
    }

    private function ensureSnapshot(TaxableDocumentData $document): void
    {
        if (! TaxDocumentSnapshot::query()
            ->where('tenant_id', $document->tenantId)
            ->where('source_type', $document->sourceType)
            ->where('source_id', $document->sourceId)
            ->exists()) {
            $this->snapshot($document);
        }
    }

    /**
     * @param callable(): list<TaxDocumentSnapshot> $snapshot
     * @param array<string, mixed> $attributes
     */
    private function postSource(
        string $sourceType,
        int $sourceId,
        int $tenantId,
        callable $snapshot,
        array $attributes,
    ): void {
        if (! TaxDocumentSnapshot::query()
            ->where('tenant_id', $tenantId)
            ->where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->exists()) {
            $snapshot();
        }

        $snapshots = TaxDocumentSnapshot::query()
            ->where('tenant_id', $tenantId)
            ->where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->get();
        foreach ($snapshots as $row) {
            if (TaxTransaction::query()->where('tax_document_snapshot_id', $row->getKey())->exists()) {
                continue;
            }
            $this->snapshots->recordTransaction($row, $attributes);
        }

        $this->snapshots->markPosted($tenantId, $sourceType, $sourceId);
    }

    private function amountFromSnapshot(TaxDocumentSnapshot $snapshot, ?string $ratio = null): TaxAmountData
    {
        if ($snapshot->tax_id === null) {
            throw new InvalidArgumentException("Tax snapshot [{$snapshot->getKey()}] is missing a tax master reference.");
        }

        $taxableAmount = (string) $snapshot->taxable_amount;
        $taxAmount = (string) $snapshot->tax_amount;
        $totalAmount = (string) $snapshot->total_amount;
        if ($ratio !== null) {
            $taxableAmount = $this->math->mul($taxableAmount, $ratio);
            $taxAmount = $this->math->mul($taxAmount, $ratio);
            $totalAmount = $this->math->mul($totalAmount, $ratio);
        }

        return new TaxAmountData(
            taxId: (int) $snapshot->tax_id,
            taxCode: (string) $snapshot->tax_code,
            taxName: (string) $snapshot->tax_name,
            taxType: (string) $snapshot->tax_type,
            calculationMethod: (string) $snapshot->calculation_method,
            rate: (string) $snapshot->rate,
            sequence: (int) $snapshot->sequence,
            taxableAmount: $taxableAmount,
            taxAmount: $taxAmount,
            totalAfterTax: $totalAmount,
            isWithholding: (bool) $snapshot->is_withholding,
            recoverable: (bool) $snapshot->recoverable,
            payable: (bool) $snapshot->payable,
            receivable: (bool) $snapshot->receivable,
        );
    }
}
