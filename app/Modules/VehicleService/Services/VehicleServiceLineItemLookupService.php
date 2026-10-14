<?php

declare(strict_types=1);

namespace Modules\VehicleService\Services;

use Modules\Inventory\Models\InventoryBatch;
use Modules\Inventory\Services\SellableBatchLookupService;
use Modules\Item\Enums\ItemType;
use Modules\Item\Models\Item;
use Modules\Item\Services\ItemQueryService;

final class VehicleServiceLineItemLookupService
{
    private const MAX_RESULTS = 50;

    public function __construct(
        private readonly ItemQueryService $items,
        private readonly SellableBatchLookupService $batches,
    ) {}

    /**
     * @return array{
     *     options: list<array{type: 'item'|'batch', model: Item|InventoryBatch}>,
     *     meta: array{current_page: int, from: int|null, last_page: int, per_page: int, to: int|null, total: int}
     * }
     */
    public function search(
        int $tenantId,
        ?int $organizationUnitId,
        string $search,
        int $page,
        int $perPage,
        bool $includeBatchOptions,
    ): array {
        $candidateLimit = min($page * $perPage, self::MAX_RESULTS);
        $criteria = ['search' => $search];
        $options = [];
        $total = 0;

        foreach ([
            'untracked-stockable',
            ItemType::Service->value,
            ItemType::Labour->value,
            ItemType::Combo->value,
            ItemType::Package->value,
        ] as $kind) {
            $results = $this->items->lookup(
                $criteria,
                $tenantId,
                $organizationUnitId,
                $candidateLimit,
                $kind,
                prioritizeSearch: true,
                page: 1,
            );

            foreach ($results->getCollection() as $item) {
                if (! $this->isSupportedItem($item)) {
                    continue;
                }

                $options[] = ['type' => 'item', 'model' => $item];
            }
            $total += $results->total();
        }

        if ($includeBatchOptions) {
            $batchResults = $this->batches->paginate(
                tenantId: $tenantId,
                organizationUnitId: $organizationUnitId,
                search: $search,
                perPage: $candidateLimit,
                prioritizeSearch: true,
                page: 1,
            );
            $total += $batchResults->total();

            foreach ($batchResults->getCollection() as $batch) {
                $options[] = ['type' => 'batch', 'model' => $batch];
            }
        }

        $options = $this->deduplicate($options);
        usort($options, fn (array $left, array $right): int => $this->compare($left, $right, $search));

        $total = min($total, self::MAX_RESULTS);
        $offset = ($page - 1) * $perPage;
        $pageOptions = array_slice($options, $offset, $perPage);
        $count = count($pageOptions);

        return [
            'options' => $pageOptions,
            'meta' => [
                'current_page' => $page,
                'from' => $count === 0 ? null : $offset + 1,
                'last_page' => max(1, (int) ceil($total / $perPage)),
                'per_page' => $perPage,
                'to' => $count === 0 ? null : $offset + $count,
                'total' => $total,
            ],
        ];
    }

    /**
     * @param  list<array{type: 'item'|'batch', model: Item|InventoryBatch}>  $options
     * @return list<array{type: 'item'|'batch', model: Item|InventoryBatch}>
     */
    private function deduplicate(array $options): array
    {
        $seen = [];

        return array_values(array_filter($options, function (array $option) use (&$seen): bool {
            $key = $option['model'] instanceof InventoryBatch
                ? 'batch:'.$option['model']->getKey()
                : 'item:'.$option['model']->getKey();

            if (isset($seen[$key])) {
                return false;
            }

            $seen[$key] = true;

            return true;
        }));
    }

    /** @param array{type: 'item'|'batch', model: Item|InventoryBatch} $left
     * @param array{type: 'item'|'batch', model: Item|InventoryBatch} $right
     */
    private function compare(array $left, array $right, string $search): int
    {
        $leftRank = $this->relevance($left, $search);
        $rightRank = $this->relevance($right, $search);
        if ($leftRank !== $rightRank) {
            return $leftRank <=> $rightRank;
        }

        $leftName = $this->name($left);
        $rightName = $this->name($right);
        $nameOrder = strnatcasecmp($leftName, $rightName);
        if ($nameOrder !== 0) {
            return $nameOrder;
        }

        $leftCode = $this->code($left);
        $rightCode = $this->code($right);
        $codeOrder = strnatcasecmp($leftCode, $rightCode);
        if ($codeOrder !== 0) {
            return $codeOrder;
        }

        $typeOrder = $left['type'] <=> $right['type'];
        if ($typeOrder !== 0) {
            return $typeOrder;
        }

        return (int) $left['model']->getKey() <=> (int) $right['model']->getKey();
    }

    /** @param array{type: 'item'|'batch', model: Item|InventoryBatch} $option */
    private function relevance(array $option, string $search): int
    {
        $term = mb_strtolower(trim($search));
        $values = [
            $this->name($option),
            $this->code($option),
        ];

        if ($option['model'] instanceof Item) {
            $values[] = (string) $option['model']->sku;
            $values[] = (string) $option['model']->barcode;
        } else {
            $values[] = (string) $option['model']->batch_number;
            $values[] = (string) $option['model']->lot_number;
            $values[] = (string) $option['model']->item?->sku;
            $values[] = (string) $option['model']->item?->barcode;
        }

        foreach ($values as $value) {
            if ($value !== '' && mb_strtolower(trim($value)) === $term) {
                return 0;
            }
        }

        foreach ($values as $value) {
            if ($value !== '' && str_starts_with(mb_strtolower(trim($value)), $term)) {
                return 1;
            }
        }

        return 2;
    }

    /** @param array{type: 'item'|'batch', model: Item|InventoryBatch} $option */
    private function name(array $option): string
    {
        return $option['model'] instanceof InventoryBatch
            ? (string) $option['model']->item?->name
            : (string) $option['model']->name;
    }

    /** @param array{type: 'item'|'batch', model: Item|InventoryBatch} $option */
    private function code(array $option): string
    {
        return $option['model'] instanceof InventoryBatch
            ? (string) $option['model']->item?->code
            : (string) $option['model']->code;
    }

    private function isSupportedItem(Item $item): bool
    {
        $type = $item->item_type instanceof \BackedEnum
            ? $item->item_type->value
            : (string) $item->item_type;

        return in_array($type, [
            ItemType::Service->value,
            ItemType::Labour->value,
            ItemType::Combo->value,
            ItemType::Package->value,
        ], true)
            || (bool) $item->is_combo
            || ($item->is_stockable && ! in_array($type, [
                ItemType::NonStock->value,
                ItemType::Service->value,
                ItemType::Labour->value,
                ItemType::Combo->value,
                ItemType::Package->value,
            ], true));
    }
}
