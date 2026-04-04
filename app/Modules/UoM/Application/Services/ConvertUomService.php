<?php
namespace Modules\UoM\Application\Services;

use Modules\UoM\Application\Contracts\ConvertUomServiceInterface;
use Modules\UoM\Domain\RepositoryInterfaces\UomConversionRepositoryInterface;

class ConvertUomService implements ConvertUomServiceInterface
{
    public function __construct(
        private readonly UomConversionRepositoryInterface $conversionRepository,
    ) {}

    public function execute(float $qty, int $fromUomId, int $toUomId, ?int $productId = null): float
    {
        $conversion = $this->conversionRepository->findByFromTo($fromUomId, $toUomId, $productId);
        if (!$conversion) {
            throw new \DomainException("No conversion found from UoM {$fromUomId} to {$toUomId}");
        }
        return $conversion->convert($qty);
    }
}
