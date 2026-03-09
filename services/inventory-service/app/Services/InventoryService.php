<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\Repositories\ProductRepositoryInterface;
use App\Contracts\Services\InventoryServiceInterface;
use App\Domain\Inventory\Models\Product;
use Illuminate\Support\Facades\DB;
use Psr\Log\LoggerInterface;

/**
 * Inventory Service
 *
 * Business logic for product and inventory management.
 * Thin controller delegates all logic here.
 */
class InventoryService implements InventoryServiceInterface
{
    public function __construct(
        private readonly ProductRepositoryInterface $productRepository,
        private readonly LoggerInterface $logger
    ) {}

    public function createProduct(array $data): Product
    {
        return DB::transaction(function () use ($data) {
            $product = $this->productRepository->create($data);
            $this->logger->info('Product created', ['product_id' => $product->id, 'sku' => $product->sku]);
            return $product;
        });
    }

    public function updateProduct(string $id, array $data): Product
    {
        return DB::transaction(function () use ($id, $data) {
            $product = $this->productRepository->update($id, $data);
            $this->logger->info('Product updated', ['product_id' => $id]);
            return $product;
        });
    }

    public function deleteProduct(string $id): bool
    {
        $result = $this->productRepository->delete($id);
        $this->logger->info('Product deleted', ['product_id' => $id]);
        return $result;
    }

    public function getProducts(string $tenantId, array $params = []): mixed
    {
        return $this->productRepository->findByTenant($tenantId, $params);
    }

    public function getProduct(string $id): Product
    {
        $product = $this->productRepository->findById($id, ['inventoryItems']);
        if (!$product) {
            throw new \RuntimeException("Product {$id} not found.");
        }
        return $product;
    }
}
