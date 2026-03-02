<?php

declare(strict_types=1);

namespace Modules\Product\Application\Services;

use Modules\Product\Application\Commands\SetUomConversionsCommand;
use Modules\Product\Application\Handlers\SetUomConversionsHandler;
use Modules\Product\Domain\Contracts\ProductRepositoryInterface;
use Modules\Product\Domain\Contracts\UomConversionRepositoryInterface;
use Modules\Product\Domain\Entities\UomConversion;

/**
 * Service orchestrating all Unit of Measure conversion operations for products.
 *
 * Controllers must interact with UOM conversion data exclusively through this
 * service. Read and conversion operations are fulfilled via the repository
 * contract; write operations are delegated to the appropriate command handler.
 */
class UomConversionService
{
    public function __construct(
        private readonly ProductRepositoryInterface $productRepository,
        private readonly UomConversionRepositoryInterface $uomConversionRepository,
        private readonly SetUomConversionsHandler $setConversionsHandler,
    ) {}

    /**
     * Verify that a product exists within the given tenant.
     */
    public function productExists(int $productId, int $tenantId): bool
    {
        return $this->productRepository->findById($productId, $tenantId) !== null;
    }

    /**
     * Retrieve all UOM conversions for a product.
     *
     * @return UomConversion[]
     */
    public function listConversions(int $productId, int $tenantId): array
    {
        return $this->uomConversionRepository->findByProduct($productId, $tenantId);
    }

    /**
     * Replace all UOM conversions for a product and return the new set.
     *
     * @return UomConversion[]
     */
    public function setConversions(SetUomConversionsCommand $command): array
    {
        return $this->setConversionsHandler->handle($command);
    }

    /**
     * Convert a quantity between two UOMs for a specific product.
     *
     * Returns null when no conversion path can be found.
     */
    public function convertQuantity(
        int $productId,
        int $tenantId,
        string $quantity,
        string $fromUom,
        string $toUom,
    ): ?string {
        return $this->uomConversionRepository->convert($productId, $tenantId, $quantity, $fromUom, $toUom);
    }
}
