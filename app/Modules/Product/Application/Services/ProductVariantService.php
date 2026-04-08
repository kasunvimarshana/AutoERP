<?php

declare(strict_types=1);

namespace Modules\Product\Application\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Core\Domain\Exceptions\NotFoundException;
use Modules\Product\Application\Contracts\ProductVariantServiceInterface;
use Modules\Product\Domain\Entities\ProductVariant;
use Modules\Product\Domain\RepositoryInterfaces\ProductVariantRepositoryInterface;

class ProductVariantService implements ProductVariantServiceInterface
{
    public function __construct(
        private readonly ProductVariantRepositoryInterface $variantRepository,
    ) {}

    public function getVariant(string $tenantId, string $id): ProductVariant
    {
        $variant = $this->variantRepository->findById($tenantId, $id);

        if ($variant === null) {
            throw new NotFoundException('ProductVariant', $id);
        }

        return $variant;
    }

    public function createVariant(string $tenantId, string $productId, array $data): ProductVariant
    {
        return DB::transaction(function () use ($tenantId, $productId, $data): ProductVariant {
            $existing = $this->variantRepository->findBySku($tenantId, $data['sku']);
            if ($existing !== null) {
                throw new \RuntimeException("Variant SKU [{$data['sku']}] already exists for this tenant.");
            }

            $now = now();
            $variant = new ProductVariant(
                id: (string) Str::uuid(),
                tenantId: $tenantId,
                productId: $productId,
                name: $data['name'],
                sku: $data['sku'],
                barcode: $data['barcode'] ?? null,
                attributes: $data['attributes'] ?? [],
                costPrice: (float) ($data['cost_price'] ?? 0),
                salePrice: (float) ($data['sale_price'] ?? 0),
                stockQuantity: (float) ($data['stock_quantity'] ?? 0),
                isActive: (bool) ($data['is_active'] ?? true),
                createdAt: $now,
                updatedAt: $now,
            );

            $this->variantRepository->save($variant);

            return $variant;
        });
    }

    public function updateVariant(string $tenantId, string $id, array $data): ProductVariant
    {
        return DB::transaction(function () use ($tenantId, $id, $data): ProductVariant {
            $existing = $this->getVariant($tenantId, $id);

            if (isset($data['sku']) && $data['sku'] !== $existing->sku) {
                $skuConflict = $this->variantRepository->findBySku($tenantId, $data['sku']);
                if ($skuConflict !== null) {
                    throw new \RuntimeException("Variant SKU [{$data['sku']}] already exists for this tenant.");
                }
            }

            $updated = new ProductVariant(
                id: $existing->id,
                tenantId: $existing->tenantId,
                productId: $existing->productId,
                name: $data['name'] ?? $existing->name,
                sku: $data['sku'] ?? $existing->sku,
                barcode: $data['barcode'] ?? $existing->barcode,
                attributes: $data['attributes'] ?? $existing->attributes,
                costPrice: (float) ($data['cost_price'] ?? $existing->costPrice),
                salePrice: (float) ($data['sale_price'] ?? $existing->salePrice),
                stockQuantity: (float) ($data['stock_quantity'] ?? $existing->stockQuantity),
                isActive: (bool) ($data['is_active'] ?? $existing->isActive),
                createdAt: $existing->createdAt,
                updatedAt: now(),
            );

            $this->variantRepository->save($updated);

            return $updated;
        });
    }

    public function deleteVariant(string $tenantId, string $id): void
    {
        DB::transaction(function () use ($tenantId, $id): void {
            $this->getVariant($tenantId, $id);
            $this->variantRepository->delete($tenantId, $id);
        });
    }

    public function getVariantsByProduct(string $tenantId, string $productId): array
    {
        return $this->variantRepository->findByProduct($tenantId, $productId);
    }
}
