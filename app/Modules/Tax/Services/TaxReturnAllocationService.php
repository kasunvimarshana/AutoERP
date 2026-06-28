<?php

declare(strict_types=1);

namespace Modules\Tax\Services;

use Illuminate\Support\Collection;
use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\Purchase\Models\PurchaseReturn;
use Modules\Purchase\Models\PurchaseReturnLine;
use Modules\Sales\Models\SalesReturn;
use Modules\Sales\Models\SalesReturnLine;
use Modules\Tax\Models\TaxDocumentSnapshot;

final class TaxReturnAllocationService
{
    public function __construct(
        private readonly DecimalMath $math,
        private readonly TaxSnapshotService $snapshots,
    ) {}

    /**
     * @return list<TaxDocumentSnapshot>
     */
    public function reversePurchaseReturn(PurchaseReturn $return, ?int $debitNoteId = null): array
    {
        $return->loadMissing('lines');

        return $this->reverseReturn(
            tenantId: (int) $return->tenant_id,
            organizationUnitId: $return->organization_unit_id,
            sourceModule: 'purchase',
            sourceType: 'purchase_return',
            sourceId: (int) $return->getKey(),
            sourceNumber: (string) $return->return_number,
            sourceDate: $return->return_date->toDateString(),
            partyType: $return->supplier_id !== null ? 'supplier' : null,
            partyId: $return->supplier_id,
            lines: $return->lines,
            noteMetadata: ['debit_note_id' => $debitNoteId],
        );
    }

    /**
     * @return list<TaxDocumentSnapshot>
     */
    public function reverseSalesReturn(SalesReturn $return, ?int $creditNoteId = null): array
    {
        $return->loadMissing('lines');

        return $this->reverseReturn(
            tenantId: (int) $return->tenant_id,
            organizationUnitId: $return->organization_unit_id,
            sourceModule: 'sales',
            sourceType: 'sales_return',
            sourceId: (int) $return->getKey(),
            sourceNumber: (string) $return->return_number,
            sourceDate: $return->return_date->toDateString(),
            partyType: $return->customer_id !== null ? 'customer' : null,
            partyId: $return->customer_id,
            lines: $return->lines,
            noteMetadata: ['credit_note_id' => $creditNoteId],
        );
    }

    /**
     * @param  Collection<int, PurchaseReturnLine|SalesReturnLine>  $lines
     * @param  array<string, mixed>  $noteMetadata
     * @return list<TaxDocumentSnapshot>
     */
    private function reverseReturn(
        int $tenantId,
        ?int $organizationUnitId,
        string $sourceModule,
        string $sourceType,
        int $sourceId,
        string $sourceNumber,
        string $sourceDate,
        ?string $partyType,
        ?int $partyId,
        Collection $lines,
        array $noteMetadata,
    ): array {
        if (TaxDocumentSnapshot::query()
            ->where('tenant_id', $tenantId)
            ->where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->exists()) {
            throw new InvalidArgumentException('Tax reversal already exists for this return document.');
        }

        $created = [];
        foreach ($lines as $line) {
            $originals = $this->originalSnapshots($tenantId, $line);
            if ($originals->isEmpty()) {
                continue;
            }

            $ratio = $this->ratio((string) $line->returned_quantity, (string) $line->source_quantity);
            foreach ($originals as $original) {
                $snapshot = $this->snapshots->createReversalSnapshot(
                    original: $original,
                    source: [
                        'tenant_id' => $tenantId,
                        'organization_unit_id' => $organizationUnitId,
                        'source_module' => $sourceModule,
                        'source_type' => $sourceType,
                        'source_id' => $sourceId,
                        'source_number' => $sourceNumber,
                        'source_date' => $sourceDate,
                    ],
                    line: [
                        'line_type' => 'return_line',
                        'line_id' => (int) $line->getKey(),
                    ],
                    ratio: $ratio,
                    metadata: array_merge([
                        'source_line_type' => $line->source_line_type,
                        'source_line_id' => $line->source_line_id,
                        'returned_quantity' => (string) $line->returned_quantity,
                        'source_quantity' => (string) $line->source_quantity,
                    ], array_filter($noteMetadata, static fn ($value): bool => $value !== null)),
                );

                $this->snapshots->recordTransaction($snapshot, [
                    'transaction_date' => $sourceDate,
                    'party_type' => $partyType,
                    'party_id' => $partyId,
                    'metadata' => $snapshot->metadata,
                ]);
                $created[] = $snapshot;
            }
        }

        $this->snapshots->markPosted($tenantId, $sourceType, $sourceId);

        return $created;
    }

    /**
     * @return Collection<int, TaxDocumentSnapshot>
     */
    private function originalSnapshots(int $tenantId, PurchaseReturnLine|SalesReturnLine $line): Collection
    {
        $sourceType = $this->snapshotSourceType((string) $line->source_line_type);
        if ($sourceType === null || $line->source_line_id === null) {
            return collect();
        }

        return TaxDocumentSnapshot::query()
            ->where('tenant_id', $tenantId)
            ->where('source_type', $sourceType)
            ->where('line_id', (int) $line->source_line_id)
            ->where('posted', true)
            ->orderBy('sequence')
            ->get();
    }

    private function snapshotSourceType(string $sourceLineType): ?string
    {
        return match ($sourceLineType) {
            'goods_receipt_note_line' => 'goods_receipt_note',
            'sales_delivery_line' => 'sales_delivery',
            'sales_order_line' => 'sales_order',
            'invoice_line' => 'invoice',
            default => null,
        };
    }

    private function ratio(string $returnedQuantity, string $sourceQuantity): string
    {
        if ($this->math->isZero($sourceQuantity)) {
            throw new InvalidArgumentException('Cannot allocate return tax against a zero source quantity.');
        }

        return $this->math->div($returnedQuantity, $sourceQuantity, 12);
    }
}
