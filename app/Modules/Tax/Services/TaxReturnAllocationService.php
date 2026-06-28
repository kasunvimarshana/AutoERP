<?php

declare(strict_types=1);

namespace Modules\Tax\Services;

use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\Tax\Data\TaxReturnDocumentData;
use Modules\Tax\Data\TaxReturnLineData;
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
    public function reverse(TaxReturnDocumentData $document): array
    {
        if (TaxDocumentSnapshot::query()
            ->where('tenant_id', $document->tenantId)
            ->where('source_type', $document->sourceType)
            ->where('source_id', $document->sourceId)
            ->exists()) {
            throw new InvalidArgumentException('Tax reversal already exists for this return document.');
        }

        $created = [];
        foreach ($document->lines as $line) {
            $originals = $this->originalSnapshots($document->tenantId, $line);
            if ($originals->isEmpty()) {
                continue;
            }

            $ratio = $this->ratio($line->returnedQuantity, $line->sourceQuantity);
            foreach ($originals as $original) {
                $metadata = array_merge([
                    'source_line_type' => $line->sourceLineType,
                    'source_line_id' => $line->sourceLineId,
                    'returned_quantity' => $line->returnedQuantity,
                    'source_quantity' => $line->sourceQuantity,
                ], $document->noteMetadata);
                $snapshot = $this->snapshots->createReversalSnapshot(
                    original: $original,
                    source: [
                        'tenant_id' => $document->tenantId,
                        'organization_unit_id' => $document->organizationUnitId,
                        'source_module' => $document->sourceModule,
                        'source_type' => $document->sourceType,
                        'source_id' => $document->sourceId,
                        'source_number' => $document->sourceNumber,
                        'source_date' => $document->sourceDate,
                    ],
                    line: [
                        'line_type' => 'return_line',
                        'line_id' => $line->returnLineId,
                    ],
                    ratio: $ratio,
                    metadata: $metadata,
                );

                $this->snapshots->recordTransaction($snapshot, [
                    'transaction_date' => $document->sourceDate,
                    'party_type' => $document->partyType,
                    'party_id' => $document->partyId,
                    'metadata' => $snapshot->metadata,
                ]);
                $created[] = $snapshot;
            }
        }

        $this->snapshots->markPosted($document->tenantId, $document->sourceType, $document->sourceId);

        return $created;
    }

    private function originalSnapshots(int $tenantId, TaxReturnLineData $line)
    {
        $sourceType = $this->snapshotSourceType($line->sourceLineType);
        if ($sourceType === null || $line->sourceLineId === null) {
            return collect();
        }

        return TaxDocumentSnapshot::query()
            ->where('tenant_id', $tenantId)
            ->where('source_type', $sourceType)
            ->where('line_id', $line->sourceLineId)
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
