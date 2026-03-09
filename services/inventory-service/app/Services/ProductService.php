<?php

namespace App\Services;

use App\Domain\Contracts\ProductRepositoryInterface;
use App\Domain\Contracts\ProductServiceInterface;
use App\Domain\Events\ProductCreated;
use App\Domain\ValueObjects\SKU;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ProductService implements ProductServiceInterface
{
    public function __construct(
        private readonly ProductRepositoryInterface $productRepository,
        private readonly EventPublisherService $eventPublisher,
    ) {
    }

    public function list(string $tenantId, array $params = []): mixed
    {
        return $this->productRepository->list($tenantId, $params);
    }

    public function create(string $tenantId, array $data): object
    {
        $sku = SKU::fromString($data['sku'] ?? '');
        $data['sku'] = (string) $sku;

        if ($this->productRepository->existsBySku($tenantId, $data['sku'])) {
            throw new \InvalidArgumentException("SKU '{$data['sku']}' already exists for this tenant.");
        }

        $data['tenant_id'] = $tenantId;
        $product = $this->productRepository->create($data);

        $this->eventPublisher->publish(new ProductCreated(
            productId:  $product->id,
            tenantId:   $tenantId,
            sku:        $product->sku,
            name:       $product->name,
            categoryId: $product->category_id,
            unitPrice:  (float) $product->unit_price,
            currency:   config('inventory.default_currency', 'USD'),
        ));

        return $product;
    }

    public function findById(string $tenantId, string $id): object
    {
        $product = $this->productRepository->findById($tenantId, $id);

        if (!$product) {
            throw new ModelNotFoundException("Product not found: {$id}");
        }

        return $product;
    }

    public function update(string $tenantId, string $id, array $data): object
    {
        $this->findById($tenantId, $id);

        if (isset($data['sku'])) {
            $sku = SKU::fromString($data['sku']);
            $data['sku'] = (string) $sku;

            if ($this->productRepository->existsBySku($tenantId, $data['sku'], $id)) {
                throw new \InvalidArgumentException("SKU '{$data['sku']}' already exists for this tenant.");
            }
        }

        return $this->productRepository->update($id, $data);
    }

    public function delete(string $tenantId, string $id): bool
    {
        $this->findById($tenantId, $id);
        return $this->productRepository->delete($id);
    }

    public function search(string $tenantId, string $query, array $filters = []): mixed
    {
        $params = array_merge($filters, ['search' => $query]);
        return $this->productRepository->searchByNameOrSku($tenantId, $query, $params);
    }

    public function getLowStockProducts(string $tenantId, ?int $threshold = null): mixed
    {
        return $this->productRepository->findLowStock($tenantId, $threshold);
    }
}
