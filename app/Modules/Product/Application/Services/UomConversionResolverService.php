<?php

declare(strict_types=1);

namespace Modules\Product\Application\Services;

use Modules\Product\Application\Contracts\UomConversionResolverServiceInterface;
use Modules\Product\Domain\Exceptions\UomConversionPathNotFoundException;
use Modules\Product\Domain\Exceptions\ProductNotFoundException;
use Modules\Product\Domain\RepositoryInterfaces\ProductRepositoryInterface;
use Modules\Product\Domain\RepositoryInterfaces\UomConversionRepositoryInterface;

class UomConversionResolverService implements UomConversionResolverServiceInterface
{
    public function __construct(
        private readonly UomConversionRepositoryInterface $uomConversionRepository,
        private readonly ProductRepositoryInterface $productRepository,
    ) {}

    public function resolveFactor(int $tenantId, ?int $productId, int $fromUomId, int $toUomId): array
    {
        if ($fromUomId === $toUomId) {
            return [
                'factor' => '1',
                'path' => [$fromUomId],
            ];
        }

        $conversions = $this->uomConversionRepository->listForResolution($tenantId, $productId);
        $graph = [];

        foreach ($conversions as $conversion) {
            $from = $conversion->getFromUomId();
            $to = $conversion->getToUomId();

            $graph[$from][$to] = $conversion->getFactor();

            if ($conversion->isBidirectional()) {
                $graph[$to][$from] = bcdiv('1', $conversion->getFactor(), 16);
            }
        }

        if (! isset($graph[$fromUomId])) {
            throw new UomConversionPathNotFoundException($fromUomId, $toUomId);
        }

        $queue = [
            [
                'node' => $fromUomId,
                'factor' => '1',
                'path' => [$fromUomId],
            ],
        ];
        $visited = [$fromUomId => true];

        while ($queue !== []) {
            $current = array_shift($queue);
            if (! is_array($current)) {
                continue;
            }

            /** @var int $node */
            $node = $current['node'];
            /** @var string $accumulatedFactor */
            $accumulatedFactor = $current['factor'];
            /** @var array<int, int> $path */
            $path = $current['path'];

            if (! isset($graph[$node])) {
                continue;
            }

            foreach ($graph[$node] as $nextNode => $edgeFactor) {
                $nextFactor = bcmul($accumulatedFactor, (string) $edgeFactor, 16);
                $nextPath = [...$path, (int) $nextNode];

                if ((int) $nextNode === $toUomId) {
                    return [
                        'factor' => $nextFactor,
                        'path' => $nextPath,
                    ];
                }

                if (isset($visited[(int) $nextNode])) {
                    continue;
                }

                $visited[(int) $nextNode] = true;
                $queue[] = [
                    'node' => (int) $nextNode,
                    'factor' => $nextFactor,
                    'path' => $nextPath,
                ];
            }
        }

        throw new UomConversionPathNotFoundException($fromUomId, $toUomId);
    }

    public function convertQuantity(int $tenantId, ?int $productId, int $fromUomId, int $toUomId, string $quantity, int $scale = 6): array
    {
        $resolved = $this->resolveFactor($tenantId, $productId, $fromUomId, $toUomId);

        $rawQuantity = bcmul($quantity, $resolved['factor'], $scale + 8);
        $roundedQuantity = number_format(round((float) $rawQuantity, $scale, PHP_ROUND_HALF_UP), $scale, '.', '');

        return [
            'quantity' => $roundedQuantity,
            'factor' => $resolved['factor'],
            'path' => $resolved['path'],
            'from_uom_id' => $fromUomId,
            'to_uom_id' => $toUomId,
        ];
    }

    public function normalizeToProductBase(int $tenantId, int $productId, int $fromUomId, string $quantity, int $scale = 6): array
    {
        $product = $this->productRepository->find($productId);

        if (! $product) {
            throw new ProductNotFoundException($productId);
        }

        $baseUomId = $product->getBaseUomId();

        $converted = $this->convertQuantity($tenantId, $productId, $fromUomId, $baseUomId, $quantity, $scale);

        return [
            'quantity' => $converted['quantity'],
            'base_uom_id' => $baseUomId,
            'factor' => $converted['factor'],
            'path' => $converted['path'],
            'from_uom_id' => $fromUomId,
        ];
    }
}
