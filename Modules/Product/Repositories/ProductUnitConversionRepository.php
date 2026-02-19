<?php

declare(strict_types=1);

namespace Modules\Product\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Modules\Core\Repositories\BaseRepository;
use Modules\Product\Models\ProductUnitConversion;

/**
 * ProductUnitConversion Repository
 *
 * Handles data access operations for ProductUnitConversion model with
 * specialized methods for conversion lookups and calculations
 */
class ProductUnitConversionRepository extends BaseRepository
{
    /**
     * Make a new ProductUnitConversion model instance.
     */
    protected function makeModel(): Model
    {
        return new ProductUnitConversion;
    }

    /**
     * Get all conversions for a product.
     */
    public function getByProduct(string $productId): Collection
    {
        return $this->model->where('product_id', $productId)
            ->with(['fromUnit', 'toUnit'])
            ->get();
    }

    /**
     * Find conversion between two units for a product.
     */
    public function findConversion(
        string $productId,
        string $fromUnitId,
        string $toUnitId
    ): ?Model {
        return $this->model->where('product_id', $productId)
            ->where('from_unit_id', $fromUnitId)
            ->where('to_unit_id', $toUnitId)
            ->first();
    }

    /**
     * Get conversions from a specific unit for a product.
     */
    public function getConversionsFromUnit(string $productId, string $fromUnitId): Collection
    {
        return $this->model->where('product_id', $productId)
            ->where('from_unit_id', $fromUnitId)
            ->with('toUnit')
            ->get();
    }

    /**
     * Get conversions to a specific unit for a product.
     */
    public function getConversionsToUnit(string $productId, string $toUnitId): Collection
    {
        return $this->model->where('product_id', $productId)
            ->where('to_unit_id', $toUnitId)
            ->with('fromUnit')
            ->get();
    }

    /**
     * Convert quantity between units.
     */
    public function convertQuantity(
        string $productId,
        string $fromUnitId,
        string $toUnitId,
        string $quantity
    ): ?string {
        $conversion = $this->findConversion($productId, $fromUnitId, $toUnitId);

        if (! $conversion) {
            $inverseConversion = $this->findConversion($productId, $toUnitId, $fromUnitId);

            if ($inverseConversion) {
                $scale = config('pricing.decimal_scale', 6);

                return bcdiv($quantity, $inverseConversion->conversion_factor, $scale);
            }

            return null;
        }

        return $conversion->convert($quantity);
    }

    /**
     * Create or update conversion.
     */
    public function createOrUpdateConversion(
        string $productId,
        string $fromUnitId,
        string $toUnitId,
        string $conversionFactor
    ): Model {
        return $this->model->updateOrCreate(
            [
                'product_id' => $productId,
                'from_unit_id' => $fromUnitId,
                'to_unit_id' => $toUnitId,
            ],
            [
                'conversion_factor' => $conversionFactor,
            ]
        );
    }

    /**
     * Delete conversion between units for a product.
     */
    public function deleteConversion(
        string $productId,
        string $fromUnitId,
        string $toUnitId
    ): bool {
        return $this->model->where('product_id', $productId)
            ->where('from_unit_id', $fromUnitId)
            ->where('to_unit_id', $toUnitId)
            ->delete() > 0;
    }

    /**
     * Get all conversions for a unit (across all products).
     */
    public function getByUnit(string $unitId): Collection
    {
        return $this->model->where('from_unit_id', $unitId)
            ->orWhere('to_unit_id', $unitId)
            ->with(['product', 'fromUnit', 'toUnit'])
            ->get();
    }

    /**
     * Check if conversion exists.
     */
    public function conversionExists(
        string $productId,
        string $fromUnitId,
        string $toUnitId
    ): bool {
        return $this->model->where('product_id', $productId)
            ->where('from_unit_id', $fromUnitId)
            ->where('to_unit_id', $toUnitId)
            ->exists();
    }

    /**
     * Get conversion chain between units.
     */
    public function findConversionChain(
        string $productId,
        string $fromUnitId,
        string $toUnitId,
        int $maxDepth = 3
    ): ?array {
        $directConversion = $this->findConversion($productId, $fromUnitId, $toUnitId);
        if ($directConversion) {
            return [
                'conversions' => [$directConversion],
                'factor' => $directConversion->conversion_factor,
            ];
        }

        $inverseConversion = $this->findConversion($productId, $toUnitId, $fromUnitId);
        if ($inverseConversion) {
            $scale = config('pricing.decimal_scale', 6);

            return [
                'conversions' => [$inverseConversion],
                'factor' => bcdiv('1', $inverseConversion->conversion_factor, $scale),
                'inverse' => true,
            ];
        }

        if ($maxDepth > 1) {
            $fromConversions = $this->getConversionsFromUnit($productId, $fromUnitId);

            foreach ($fromConversions as $intermediate) {
                $chain = $this->findConversionChain(
                    $productId,
                    $intermediate->to_unit_id,
                    $toUnitId,
                    $maxDepth - 1
                );

                if ($chain) {
                    array_unshift($chain['conversions'], $intermediate);
                    $scale = config('pricing.decimal_scale', 6);
                    $chain['factor'] = bcmul(
                        $intermediate->conversion_factor,
                        $chain['factor'],
                        $scale
                    );

                    return $chain;
                }
            }
        }

        return null;
    }

    /**
     * Bulk create conversions for a product.
     */
    public function bulkCreateConversions(string $productId, array $conversions): int
    {
        $records = [];
        $timestamp = now();

        foreach ($conversions as $conversion) {
            $records[] = [
                'tenant_id' => $conversion['tenant_id'] ?? null,
                'product_id' => $productId,
                'from_unit_id' => $conversion['from_unit_id'],
                'to_unit_id' => $conversion['to_unit_id'],
                'conversion_factor' => $conversion['conversion_factor'],
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ];
        }

        return $this->model->insert($records) ? count($records) : 0;
    }

    /**
     * Delete all conversions for a product.
     */
    public function deleteByProduct(string $productId): int
    {
        return $this->model->where('product_id', $productId)->delete();
    }

    /**
     * Get products with unit conversions from a specific unit.
     */
    public function getProductsWithConversionFromUnit(string $unitId): Collection
    {
        return $this->model->where('from_unit_id', $unitId)
            ->with('product')
            ->get()
            ->pluck('product')
            ->unique('id');
    }

    /**
     * Get products with unit conversions to a specific unit.
     */
    public function getProductsWithConversionToUnit(string $unitId): Collection
    {
        return $this->model->where('to_unit_id', $unitId)
            ->with('product')
            ->get()
            ->pluck('product')
            ->unique('id');
    }
}
