<?php

declare(strict_types=1);

namespace Modules\Product\Application\Services;

use Modules\Product\Application\Commands\CreateProductCommand;
use Modules\Product\Application\Commands\DeleteProductCommand;
use Modules\Product\Application\Commands\UpdateProductCommand;
use Modules\Product\Application\Handlers\CreateProductHandler;
use Modules\Product\Application\Handlers\DeleteProductHandler;
use Modules\Product\Application\Handlers\UpdateProductHandler;
use Modules\Product\Domain\Contracts\ProductRepositoryInterface;
use Modules\Product\Domain\Entities\Product;

/**
 * Service orchestrating all product-related operations.
 *
 * Controllers must interact with the product domain exclusively through this
 * service. Read operations are fulfilled directly via the repository contract;
 * write operations are delegated to the appropriate command handlers.
 */
class ProductService
{
    public function __construct(
        private readonly ProductRepositoryInterface $productRepository,
        private readonly CreateProductHandler $createProductHandler,
        private readonly UpdateProductHandler $updateProductHandler,
        private readonly DeleteProductHandler $deleteProductHandler,
    ) {}

    /**
     * Retrieve a paginated list of products for the given tenant.
     *
     * @return array{items: Product[], current_page: int, last_page: int, per_page: int, total: int}
     */
    public function listProducts(int $tenantId, int $page, int $perPage): array
    {
        return $this->productRepository->findAll($tenantId, $page, $perPage);
    }

    /**
     * Find a single product by its identifier within the given tenant.
     */
    public function findProductById(int $productId, int $tenantId): ?Product
    {
        return $this->productRepository->findById($productId, $tenantId);
    }

    /**
     * Create a new product and return the persisted entity.
     */
    public function createProduct(CreateProductCommand $command): Product
    {
        return $this->createProductHandler->handle($command);
    }

    /**
     * Update an existing product and return the updated entity.
     */
    public function updateProduct(UpdateProductCommand $command): Product
    {
        return $this->updateProductHandler->handle($command);
    }

    /**
     * Soft-delete a product.
     */
    public function deleteProduct(DeleteProductCommand $command): void
    {
        $this->deleteProductHandler->handle($command);
    }
}
