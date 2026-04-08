<?php

namespace App\Services;

use App\Models\DocumentSequence;
use Illuminate\Support\Facades\DB;

/**
 * DocumentSequenceService
 *
 * Thread-safe sequential document number generation.
 * Uses SELECT FOR UPDATE to prevent gaps or duplicates under concurrent writes.
 *
 * Output examples:
 *   PO-2024-000001   (purchase_order)
 *   GRN-2024-000002  (goods_receipt)
 *   SO-2024-000001   (sales_order)
 *   JRN-2024-000142  (journal / ledger)
 *   TRF-2024-000005  (transfer)
 *   ADJ-2024-000003  (adjustment)
 *   RMA-2024-000001  (return_authorization)
 *   PROD-2024-000001 (production_order)
 */
class DocumentSequenceService
{
    private static array $prefixes = [
        'po'           => 'PO',
        'grn'          => 'GRN',
        'so'           => 'SO',
        'journal'      => 'JRN',
        'transfer'     => 'TRF',
        'adjustment'   => 'ADJ',
        'pick'         => 'PCK',
        'shipment'     => 'SHP',
        'rma'          => 'RMA',
        'production'   => 'PROD',
        'physical_count' => 'CNT',
        'bom'          => 'BOM',
    ];

    public function next(int $organizationId, string $documentType): string
    {
        return DB::transaction(function () use ($organizationId, $documentType) {
            $prefix = self::$prefixes[$documentType] ?? strtoupper($documentType);

            $seq = DocumentSequence::where('organization_id', $organizationId)
                ->where('document_type', $documentType)
                ->lockForUpdate()
                ->firstOrCreate(
                    ['organization_id' => $organizationId, 'document_type' => $documentType],
                    [
                        'prefix'        => $prefix,
                        'next_number'   => 1,
                        'padding'       => 6,
                        'separator'     => '-',
                        'include_year'  => true,
                        'include_month' => false,
                        'reset_on_year' => false,
                    ]
                );

            $number = str_pad($seq->next_number, $seq->padding, '0', STR_PAD_LEFT);
            $parts  = [$seq->prefix];

            if ($seq->include_year)  $parts[] = now()->year;
            if ($seq->include_month) $parts[] = str_pad(now()->month, 2, '0', STR_PAD_LEFT);

            $parts[] = $number;
            $ref     = implode($seq->separator, $parts);

            $seq->increment('next_number');

            return $ref;
        });
    }

    public function peek(int $organizationId, string $documentType): string
    {
        $seq = DocumentSequence::where('organization_id', $organizationId)
            ->where('document_type', $documentType)
            ->first();

        if (!$seq) return 'N/A';

        $prefix = self::$prefixes[$documentType] ?? strtoupper($documentType);
        $number = str_pad($seq->next_number, $seq->padding, '0', STR_PAD_LEFT);
        return "{$prefix}-" . now()->year . "-{$number}";
    }
}
