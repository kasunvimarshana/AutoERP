<?php

declare(strict_types=1);

namespace Modules\Product\Application\Services;

use Modules\Product\Application\Contracts\ConvertUomServiceInterface;
use Modules\Product\Domain\RepositoryInterfaces\UomConversionRepositoryInterface;

class ConvertUomService implements ConvertUomServiceInterface
{
    public function __construct(private readonly UomConversionRepositoryInterface $uomConversionRepository) {}

    public function convert(int $fromUomId, int $toUomId, string $quantity): string
    {
        if ($fromUomId === $toUomId) {
            return $quantity;
        }

        $uomConversion = $this->uomConversionRepository->findByUomPair($fromUomId, $toUomId);

        if ($uomConversion !== null) {
            return bcmul($quantity, (string) $uomConversion->getFactor(), 6);
        }

        $reverseConversion = $this->uomConversionRepository->findByUomPair($toUomId, $fromUomId);

        if ($reverseConversion !== null) {
            return bcdiv($quantity, (string) $reverseConversion->getFactor(), 6);
        }

        throw new \InvalidArgumentException(
            "No UOM conversion found for UOM pair ({$fromUomId} -> {$toUomId})."
        );
    }
}
