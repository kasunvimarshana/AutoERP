<?php

declare(strict_types=1);

namespace Modules\Reporting\Services;

use Modules\Inventory\Enums\ValuationMethod;
use Modules\Inventory\Models\InventoryValuationLayer;
use Modules\Inventory\Services\InventorySourceDocumentResolver;

final class InventoryAgingRows
{
    public function __construct(private readonly InventorySourceDocumentResolver $documents) {}

    /** @param array<int, InventoryValuationLayer> $layers */
    public function prepare(array $layers): void
    {
        if ($layers === []) {
            return;
        }

        $first = $layers[array_key_first($layers)];
        $receiptLayers = array_values(array_filter(
            $layers,
            static fn (InventoryValuationLayer $layer): bool => $layer->valuation_method !== ValuationMethod::WeightedAverage,
        ));
        $documents = $this->documents->resolve(
            $receiptLayers,
            (int) $first->tenant_id,
            $first->organization_unit_id === null ? null : (int) $first->organization_unit_id,
        );

        foreach ($layers as $layer) {
            $document = $documents[(string) $layer->source_type][(int) $layer->source_id] ?? null;
            if ($layer->valuation_method === ValuationMethod::WeightedAverage) {
                $document = ['label' => 'Weighted-average pool', 'url' => null];
            }

            $layer->setAttribute('source_document_label', $document['label'] ?? $layer->movement?->movement_number ?? 'Stock entry');
            $layer->setAttribute('source_document_url', $document['url'] ?? null);
        }
    }
}
