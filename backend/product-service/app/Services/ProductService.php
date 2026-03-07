<?php

namespace App\Services;

use App\DTOs\ProductDTO;
use App\Events\ProductCreated;
use App\Events\ProductDeleted;
use App\Events\ProductUpdated;
use App\Models\Product;
use App\Repositories\Interfaces\ProductRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProductService
{
    public function __construct(
        private readonly ProductRepositoryInterface $productRepository,
        private readonly InventoryServiceClient $inventoryClient,
    ) {}

    public function list(array $filters = [], ?int $perPage = null): LengthAwarePaginator|Collection
    {
        return $this->productRepository->paginate($filters, $perPage);
    }

    public function findById(int $id): Product
    {
        return $this->productRepository->findById($id)
            ?? throw new \Illuminate\Database\Eloquent\ModelNotFoundException('Product not found.');
    }

    public function create(array $data): Product
    {
        return DB::transaction(function () use ($data) {
            if ($this->productRepository->findBySku($data['sku'])) {
                throw ValidationException::withMessages([
                    'sku' => ['SKU already exists in this tenant.'],
                ]);
            }

            $dto = ProductDTO::fromArray($data);
            $product = $this->productRepository->create($dto->toArray());

            event(new ProductCreated($product));

            return $product;
        });
    }

    public function update(int $id, array $data): Product
    {
        return DB::transaction(function () use ($id, $data) {
            $product = $this->findById($id);

            if (isset($data['sku']) && $data['sku'] !== $product->sku) {
                $existing = $this->productRepository->findBySku($data['sku']);
                if ($existing && $existing->id !== $id) {
                    throw ValidationException::withMessages(['sku' => ['SKU already exists.']]);
                }
            }

            $updated = $this->productRepository->update($product, $data);

            event(new ProductUpdated($updated));

            return $updated;
        });
    }

    public function delete(int $id): void
    {
        DB::transaction(function () use ($id) {
            $product = $this->findById($id);
            event(new ProductDeleted($product));
            $this->productRepository->delete($product);
        });
    }

    public function getInventory(int $productId): array
    {
        $product = $this->findById($productId);

        return $this->inventoryClient->getInventoryForProduct($product->id, $product->tenant_id);
    }
}
