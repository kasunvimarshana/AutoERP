<?php

declare(strict_types=1);

namespace Modules\Product\Application\Services;

use Modules\Product\Application\Commands\DeleteProductAttributeCommand;
use Modules\Product\Application\Commands\SetProductAttributesCommand;
use Modules\Product\Application\Handlers\DeleteProductAttributeHandler;
use Modules\Product\Application\Handlers\SetProductAttributesHandler;
use Modules\Product\Domain\Contracts\ProductAttributeRepositoryInterface;
use Modules\Product\Domain\Contracts\ProductRepositoryInterface;
use Modules\Product\Domain\Entities\ProductAttribute;

/**
 * Service orchestrating all product dynamic attribute operations.
 *
 * Controllers must interact with product attribute data exclusively through this
 * service. Read operations are fulfilled directly via the repository contract;
 * write operations are delegated to the appropriate command handlers.
 */
class ProductAttributeService
{
    public function __construct(
        private readonly ProductRepositoryInterface $productRepository,
        private readonly ProductAttributeRepositoryInterface $productAttributeRepository,
        private readonly SetProductAttributesHandler $setAttributesHandler,
        private readonly DeleteProductAttributeHandler $deleteAttributeHandler,
    ) {}

    /**
     * Verify that a product exists within the given tenant.
     */
    public function productExists(int $productId, int $tenantId): bool
    {
        return $this->productRepository->findById($productId, $tenantId) !== null;
    }

    /**
     * Retrieve all dynamic attributes for a product, ordered by sort_order.
     *
     * @return ProductAttribute[]
     */
    public function listAttributes(int $productId, int $tenantId): array
    {
        return $this->productAttributeRepository->findByProduct($productId, $tenantId);
    }

    /**
     * Replace all dynamic attributes for a product and return the new set.
     *
     * @return ProductAttribute[]
     */
    public function setAttributes(SetProductAttributesCommand $command): array
    {
        return $this->setAttributesHandler->handle($command);
    }

    /**
     * Remove a single product attribute by its identifier.
     */
    public function deleteAttribute(DeleteProductAttributeCommand $command): void
    {
        $this->deleteAttributeHandler->handle($command);
    }
}
