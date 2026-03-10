<?php

declare(strict_types=1);

namespace App\Application\Product\Services;

use App\Application\Product\DTOs\CreateProductDTO;
use App\Application\Product\DTOs\UpdateProductDTO;
use App\Domain\Product\Exceptions\ProductAlreadyExistsException;
use App\Domain\Product\Exceptions\ProductNotFoundException;
use App\Domain\Product\Repositories\ProductRepositoryInterface;
use App\Infrastructure\Messaging\EventPublisher;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;

/**
 * ProductService
 *
 * Orchestrates all product business logic.  The controller calls only
 * methods on this service; the service calls the repository.
 */
class ProductService
{
    public function __construct(
        private readonly ProductRepositoryInterface $productRepository,
        private readonly EventPublisher             $eventPublisher,
    ) {}

    // ─────────────────────────────────────────────────────────────────────────
    // Queries
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * List products for a tenant with dynamic filters, sorting, and pagination.
     *
     * @param  string                $tenantId
     * @param  array<string, mixed>  $filters
     * @param  int                   $perPage
     * @param  array<string>         $relations
     * @param  array<string, string> $orderBy
     * @return LengthAwarePaginator
     */
    public function list(
        string $tenantId,
        array  $filters   = [],
        int    $perPage   = 15,
        array  $relations = ['category'],
        array  $orderBy   = ['created_at' => 'desc']
    ): LengthAwarePaginator {
        return $this->productRepository->listForTenant(
            $tenantId,
            $filters,
            $perPage,
            $relations,
            $orderBy
        );
    }

    /**
     * Full-text search across products.
     *
     * @param  string $term
     * @param  string $tenantId
     * @param  int    $perPage
     * @return LengthAwarePaginator
     */
    public function search(string $term, string $tenantId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->productRepository->searchForTenant($term, $tenantId, $perPage);
    }

    /**
     * Get a single product by ID.
     *
     * @param  string $id
     * @param  string $tenantId
     * @return \App\Infrastructure\Persistence\Models\Product
     *
     * @throws ProductNotFoundException
     */
    public function findById(string $id, string $tenantId): \App\Infrastructure\Persistence\Models\Product
    {
        $product = $this->productRepository->findBy(
            ['id' => $id, 'tenant_id' => $tenantId],
            ['*'],
            ['category']
        );

        if ($product === null) {
            throw new ProductNotFoundException($id);
        }

        return $product;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Commands
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Create a new product.
     *
     * @param  CreateProductDTO $dto
     * @return \App\Infrastructure\Persistence\Models\Product
     *
     * @throws ProductAlreadyExistsException
     */
    public function create(CreateProductDTO $dto): \App\Infrastructure\Persistence\Models\Product
    {
        // Guard: SKU must be unique per tenant
        if ($this->productRepository->findByCode($dto->code, $dto->tenantId) !== null) {
            throw new ProductAlreadyExistsException($dto->code, $dto->tenantId);
        }

        $product = $this->productRepository->create($dto->toArray());

        // Publish domain event
        $this->eventPublisher->publish('kvsaas.events', 'product.created', [
            'product_id' => $product->id,
            'tenant_id'  => $dto->tenantId,
            'name'       => $product->name,
            'code'       => $product->code,
            'sku'        => $product->sku,
        ]);

        Log::info('Product created', ['product_id' => $product->id, 'tenant_id' => $dto->tenantId]);

        return $product->loadMissing('category');
    }

    /**
     * Update an existing product.
     *
     * @param  string           $id
     * @param  UpdateProductDTO $dto
     * @return \App\Infrastructure\Persistence\Models\Product
     *
     * @throws ProductNotFoundException
     */
    public function update(string $id, UpdateProductDTO $dto): \App\Infrastructure\Persistence\Models\Product
    {
        $this->findById($id, $dto->tenantId); // ensures tenant ownership

        $product = $this->productRepository->update($id, $dto->toArray());

        $this->eventPublisher->publish('kvsaas.events', 'product.updated', [
            'product_id' => $id,
            'tenant_id'  => $dto->tenantId,
        ]);

        return $product->loadMissing('category');
    }

    /**
     * Delete a product (soft delete).
     *
     * @param  string $id
     * @param  string $tenantId
     * @return void
     *
     * @throws ProductNotFoundException
     */
    public function delete(string $id, string $tenantId): void
    {
        $this->findById($id, $tenantId); // ensures ownership

        $this->productRepository->softDelete($id);

        $this->eventPublisher->publish('kvsaas.events', 'product.deleted', [
            'product_id' => $id,
            'tenant_id'  => $tenantId,
        ]);
    }
}
