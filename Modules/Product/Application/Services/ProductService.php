<?php

declare(strict_types=1);

namespace Modules\Product\Application\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Modules\Core\Application\Helpers\DecimalHelper;
use Modules\Core\Domain\Contracts\ServiceContract;
use Modules\Product\Application\DTOs\CreateProductDTO;
use Modules\Product\Domain\Contracts\ProductRepositoryContract;
use Modules\Product\Domain\Contracts\UomRepositoryContract;
use Modules\Product\Domain\Entities\ProductVariant;
use Modules\Product\Domain\Entities\UomConversion;
use RuntimeException;

/**
 * Product service.
 *
 * Orchestrates all product catalog use cases.
 * All mutations are wrapped in DB::transaction to ensure atomicity.
 * No business logic in controllers — everything is delegated here.
 * All UOM conversion arithmetic uses BCMath via DecimalHelper — never float.
 */
class ProductService implements ServiceContract
{
    public function __construct(
        private readonly ProductRepositoryContract $productRepository,
        private readonly UomRepositoryContract $uomRepository,
    ) {}

    /**
     * Return a paginated list of products for the current tenant.
     */
    public function list(int $perPage = 15): LengthAwarePaginator
    {
        return $this->productRepository->paginate($perPage);
    }

    /**
     * Create a new product.
     */
    public function create(CreateProductDTO $dto): Model
    {
        return DB::transaction(function () use ($dto): Model {
            return $this->productRepository->create([
                'name'                => $dto->name,
                'sku'                 => $dto->sku,
                'type'                => $dto->type,
                'description'         => $dto->description,
                'uom_id'              => $dto->uomId,
                'buying_uom_id'       => $dto->buyingUomId,
                'selling_uom_id'      => $dto->sellingUomId,
                'is_active'           => $dto->isActive,
                'has_serial_tracking' => $dto->hasSerialTracking,
                'has_batch_tracking'  => $dto->hasBatchTracking,
                'has_expiry_tracking' => $dto->hasExpiryTracking,
                'barcode'             => $dto->barcode,
            ]);
        });
    }

    /**
     * Show a single product by ID.
     */
    public function show(int|string $id): Model
    {
        return $this->productRepository->findOrFail($id);
    }

    /**
     * Update an existing product.
     *
     * @param array<string, mixed> $data
     */
    public function update(int|string $id, array $data): Model
    {
        return DB::transaction(function () use ($id, $data): Model {
            return $this->productRepository->update($id, $data);
        });
    }

    /**
     * Delete a product.
     */
    public function delete(int|string $id): bool
    {
        return DB::transaction(function () use ($id): bool {
            return $this->productRepository->delete($id);
        });
    }

    /**
     * Convert a quantity from one UOM to another using the product-specific conversion factor.
     *
     * Uses BCMath (DecimalHelper) exclusively — no floating-point arithmetic.
     * Supports direct path (from → to) and inverse reciprocal path (to → from).
     *
     * @throws RuntimeException when no conversion factor is found for the given UOM pair.
     */
    public function convertUom(
        string $productId,
        string $quantity,
        int $fromUomId,
        int $toUomId
    ): string {
        if ($fromUomId === $toUomId) {
            return $quantity;
        }

        // Try direct path first
        $conversion = UomConversion::query()
            ->where('product_id', $productId)
            ->where('from_uom_id', $fromUomId)
            ->where('to_uom_id', $toUomId)
            ->first();

        if ($conversion !== null) {
            return DecimalHelper::mul($quantity, $conversion->factor, DecimalHelper::SCALE_INTERMEDIATE);
        }

        // Try inverse reciprocal path
        $inverse = UomConversion::query()
            ->where('product_id', $productId)
            ->where('from_uom_id', $toUomId)
            ->where('to_uom_id', $fromUomId)
            ->first();

        if ($inverse !== null) {
            return DecimalHelper::div($quantity, $inverse->factor, DecimalHelper::SCALE_INTERMEDIATE);
        }

        throw new RuntimeException(
            "No UOM conversion factor found for product [{$productId}] from UOM [{$fromUomId}] to UOM [{$toUomId}]."
        );
    }

    /**
     * Create a variant for a product.
     *
     * @param array<string, mixed> $attributes
     */
    public function createVariant(int $productId, array $attributes): ProductVariant
    {
        return DB::transaction(function () use ($productId, $attributes): ProductVariant {
            /** @var ProductVariant $variant */
            $variant = ProductVariant::create([
                'product_id'   => $productId,
                'variant_name' => $attributes['variant_name'],
                'sku'          => $attributes['sku'] ?? null,
                'attributes'   => $attributes['attributes'] ?? [],
                'is_active'    => $attributes['is_active'] ?? true,
            ]);

            return $variant;
        });
    }

    /**
     * Return all variants for a given product.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, ProductVariant>
     */
    public function listVariants(int $productId): \Illuminate\Database\Eloquent\Collection
    {
        return ProductVariant::query()
            ->where('product_id', $productId)
            ->get();
    }

    /**
     * Show a single product variant by ID.
     */
    public function showVariant(int $variantId): ProductVariant
    {
        /** @var ProductVariant $variant */
        $variant = ProductVariant::findOrFail($variantId);

        return $variant;
    }

    /**
     * Delete a product variant by ID.
     */
    public function deleteVariant(int $variantId): bool
    {
        return DB::transaction(function () use ($variantId): bool {
            /** @var ProductVariant $variant */
            $variant = ProductVariant::findOrFail($variantId);

            return (bool) $variant->delete();
        });
    }
}
