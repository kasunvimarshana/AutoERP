<?php

declare(strict_types=1);

namespace Modules\Product\Application\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Modules\Core\Domain\Exceptions\NotFoundException;
use Modules\Product\Application\Contracts\ProductServiceInterface;
use Modules\Product\Domain\Entities\Product;
use Modules\Product\Domain\Events\ProductCreated;
use Modules\Product\Domain\Events\ProductUpdated;
use Modules\Product\Domain\RepositoryInterfaces\ProductRepositoryInterface;

class ProductService implements ProductServiceInterface
{
    public function __construct(
        private readonly ProductRepositoryInterface $productRepository,
    ) {}

    public function getProduct(string $tenantId, string $id): Product
    {
        $product = $this->productRepository->findById($tenantId, $id);

        if ($product === null) {
            throw new NotFoundException('Product', $id);
        }

        return $product;
    }

    public function createProduct(string $tenantId, array $data): Product
    {
        return DB::transaction(function () use ($tenantId, $data): Product {
            $existing = $this->productRepository->findBySku($tenantId, $data['sku']);
            if ($existing !== null) {
                throw new \RuntimeException("SKU [{$data['sku']}] already exists for this tenant.");
            }

            $now = now();
            $product = new Product(
                id: (string) Str::uuid(),
                tenantId: $tenantId,
                categoryId: $data['category_id'] ?? null,
                name: $data['name'],
                sku: $data['sku'],
                barcode: $data['barcode'] ?? null,
                type: $data['type'] ?? 'physical',
                status: $data['status'] ?? 'draft',
                description: $data['description'] ?? null,
                shortDescription: $data['short_description'] ?? null,
                unit: $data['unit'] ?? 'each',
                weight: isset($data['weight']) ? (float) $data['weight'] : null,
                weightUnit: $data['weight_unit'] ?? null,
                hasVariants: (bool) ($data['has_variants'] ?? false),
                isTrackable: (bool) ($data['is_trackable'] ?? true),
                isSerialTracked: (bool) ($data['is_serial_tracked'] ?? false),
                isBatchTracked: (bool) ($data['is_batch_tracked'] ?? false),
                costPrice: (float) ($data['cost_price'] ?? 0),
                salePrice: (float) ($data['sale_price'] ?? 0),
                minStockLevel: (float) ($data['min_stock_level'] ?? 0),
                reorderPoint: (float) ($data['reorder_point'] ?? 0),
                taxGroupId: $data['tax_group_id'] ?? null,
                imageUrl: $data['image_url'] ?? null,
                metadata: $data['metadata'] ?? [],
                createdAt: $now,
                updatedAt: $now,
            );

            $this->productRepository->save($product);

            Event::dispatch(new ProductCreated($product));

            return $product;
        });
    }

    public function updateProduct(string $tenantId, string $id, array $data): Product
    {
        return DB::transaction(function () use ($tenantId, $id, $data): Product {
            $existing = $this->getProduct($tenantId, $id);

            if (isset($data['sku']) && $data['sku'] !== $existing->sku) {
                $skuConflict = $this->productRepository->findBySku($tenantId, $data['sku']);
                if ($skuConflict !== null) {
                    throw new \RuntimeException("SKU [{$data['sku']}] already exists for this tenant.");
                }
            }

            $updated = new Product(
                id: $existing->id,
                tenantId: $existing->tenantId,
                categoryId: $data['category_id'] ?? $existing->categoryId,
                name: $data['name'] ?? $existing->name,
                sku: $data['sku'] ?? $existing->sku,
                barcode: $data['barcode'] ?? $existing->barcode,
                type: $data['type'] ?? $existing->type,
                status: $data['status'] ?? $existing->status,
                description: $data['description'] ?? $existing->description,
                shortDescription: $data['short_description'] ?? $existing->shortDescription,
                unit: $data['unit'] ?? $existing->unit,
                weight: isset($data['weight']) ? (float) $data['weight'] : $existing->weight,
                weightUnit: $data['weight_unit'] ?? $existing->weightUnit,
                hasVariants: (bool) ($data['has_variants'] ?? $existing->hasVariants),
                isTrackable: (bool) ($data['is_trackable'] ?? $existing->isTrackable),
                isSerialTracked: (bool) ($data['is_serial_tracked'] ?? $existing->isSerialTracked),
                isBatchTracked: (bool) ($data['is_batch_tracked'] ?? $existing->isBatchTracked),
                costPrice: (float) ($data['cost_price'] ?? $existing->costPrice),
                salePrice: (float) ($data['sale_price'] ?? $existing->salePrice),
                minStockLevel: (float) ($data['min_stock_level'] ?? $existing->minStockLevel),
                reorderPoint: (float) ($data['reorder_point'] ?? $existing->reorderPoint),
                taxGroupId: $data['tax_group_id'] ?? $existing->taxGroupId,
                imageUrl: $data['image_url'] ?? $existing->imageUrl,
                metadata: $data['metadata'] ?? $existing->metadata,
                createdAt: $existing->createdAt,
                updatedAt: now(),
            );

            $this->productRepository->save($updated);

            Event::dispatch(new ProductUpdated($updated));

            return $updated;
        });
    }

    public function deleteProduct(string $tenantId, string $id): void
    {
        DB::transaction(function () use ($tenantId, $id): void {
            $this->getProduct($tenantId, $id);
            $this->productRepository->delete($tenantId, $id);
        });
    }

    public function getAllProducts(string $tenantId): array
    {
        return $this->productRepository->findAll($tenantId);
    }

    public function getProductsByCategory(string $tenantId, string $categoryId): array
    {
        return $this->productRepository->findByCategory($tenantId, $categoryId);
    }

    public function getProductsByType(string $tenantId, string $type): array
    {
        return $this->productRepository->findByType($tenantId, $type);
    }
}
