<?php

namespace Services\Product\Domain;

use Shared\Contracts\ProductRepositoryInterface;
use Shared\Traits\HasTenantScope;

/**
 * Domain-Driven Design: Product Service
 * Handles business logic for product management.
 */
class ProductService
{
    private ProductRepositoryInterface $repository;

    public function __construct(ProductRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Creates a new product (Physical, Digital, Bundle, etc.)
     */
    public function createProduct(array $data): object
    {
        // 1. Business Validation (e.g., SKU uniqueness per tenant)
        // 2. Multi-UOM Conversion logic
        // 3. Persist via Repository
        $product = $this->repository->create($data);

        // 4. Emit Event via Outbox Pattern
        // EventBus::publish(new ProductCreatedEvent($product));

        return $product;
    }

    /**
     * Retrieves a product by ID (Tenant-scoped via Repository)
     */
    public function getProduct(string $id): object
    {
        return $this->repository->find($id);
    }
}
