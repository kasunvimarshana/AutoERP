<?php

declare(strict_types=1);

namespace Modules\Inventory\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Modules\Inventory\Entities\UnitOfMeasure;
use Modules\Inventory\Repositories\Contracts\UnitOfMeasureRepositoryInterface;

/**
 * Unit of Measure Service
 *
 * Handles business logic for UoM management, conversions, and category operations.
 */
class UnitOfMeasureService
{
    /**
     * UnitOfMeasureService constructor.
     */
    public function __construct(
        protected UnitOfMeasureRepositoryInterface $uomRepository
    ) {}

    /**
     * Get paginated list of units of measure.
     */
    public function getPaginated(int $perPage = 15): LengthAwarePaginator
    {
        return $this->uomRepository->paginate($perPage);
    }

    /**
     * Get all active units of measure.
     */
    public function getAllActive(): Collection
    {
        return $this->uomRepository->getActiveUoms();
    }

    /**
     * Get units of measure by category.
     */
    public function getByCategory(string $category): Collection
    {
        return $this->uomRepository->getByCategory($category);
    }

    /**
     * Get all base units.
     */
    public function getBaseUnits(): Collection
    {
        return $this->uomRepository->getBaseUnits();
    }

    /**
     * Find a unit of measure by ID.
     */
    public function findById(string $id): ?UnitOfMeasure
    {
        return $this->uomRepository->findById($id);
    }

    /**
     * Find a unit of measure by code.
     */
    public function findByCode(string $code): ?UnitOfMeasure
    {
        return $this->uomRepository->findByCode($code);
    }

    /**
     * Create a new unit of measure.
     *
     * @throws \Exception
     */
    public function create(array $data): UnitOfMeasure
    {
        DB::beginTransaction();
        try {
            // Validate business rules
            $this->validateUomCreation($data);

            // Set default ratio for base units
            if (!empty($data['is_base_unit']) && $data['is_base_unit']) {
                $data['ratio'] = 1.0;
            }

            $uom = $this->uomRepository->create($data);

            DB::commit();
            return $uom;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Update an existing unit of measure.
     *
     * @throws \Exception
     */
    public function update(string $id, array $data): UnitOfMeasure
    {
        DB::beginTransaction();
        try {
            $uom = $this->uomRepository->findById($id);

            if (!$uom) {
                throw new \Exception("Unit of Measure not found: {$id}");
            }

            // Validate business rules
            $this->validateUomUpdate($uom, $data);

            // Prevent changing base unit ratio
            if ($uom->is_base_unit && isset($data['ratio']) && $data['ratio'] != 1.0) {
                throw new \Exception('Cannot change ratio for base unit. It must remain 1.0');
            }

            $uom = $this->uomRepository->update($uom, $data);

            DB::commit();
            return $uom;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Delete a unit of measure.
     *
     * @throws \Exception
     */
    public function delete(string $id): bool
    {
        DB::beginTransaction();
        try {
            $uom = $this->uomRepository->findById($id);

            if (!$uom) {
                throw new \Exception("Unit of Measure not found: {$id}");
            }

            // Check if UoM is in use
            if ($uom->products()->count() > 0) {
                throw new \Exception('Cannot delete unit of measure that is used by products');
            }

            $result = $this->uomRepository->delete($uom);

            DB::commit();
            return $result;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Convert quantity between two units of measure.
     *
     * @throws \Exception
     */
    public function convertQuantity(string $fromUomId, string $toUomId, float $quantity): float
    {
        $fromUom = $this->uomRepository->findById($fromUomId);
        $toUom = $this->uomRepository->findById($toUomId);

        if (!$fromUom || !$toUom) {
            throw new \Exception('Unit of Measure not found');
        }

        $convertedQuantity = $fromUom->convertTo($quantity, $toUom);

        if ($convertedQuantity === null) {
            throw new \Exception(
                "Cannot convert between different UoM categories: {$fromUom->uom_category} and {$toUom->uom_category}"
            );
        }

        return $convertedQuantity;
    }

    /**
     * Validate unit of measure creation.
     *
     * @throws \Exception
     */
    protected function validateUomCreation(array $data): void
    {
        // Check for duplicate code
        if (isset($data['code'])) {
            $existing = $this->uomRepository->findByCode($data['code']);
            if ($existing) {
                throw new \Exception("Unit of Measure with code '{$data['code']}' already exists");
            }
        }

        // Validate ratio for non-base units
        if (empty($data['is_base_unit']) || !$data['is_base_unit']) {
            if (empty($data['ratio']) || $data['ratio'] <= 0) {
                throw new \Exception('Ratio must be greater than 0 for non-base units');
            }
        }

        // Check if category already has a base unit
        if (!empty($data['is_base_unit']) && $data['is_base_unit']) {
            $existingBaseUnit = $this->uomRepository->getByCategory($data['uom_category'])
                ->where('is_base_unit', true)
                ->first();

            if ($existingBaseUnit) {
                throw new \Exception(
                    "Category '{$data['uom_category']}' already has a base unit: {$existingBaseUnit->name}"
                );
            }
        }
    }

    /**
     * Validate unit of measure update.
     *
     * @throws \Exception
     */
    protected function validateUomUpdate(UnitOfMeasure $uom, array $data): void
    {
        // Check for duplicate code (if code is being changed)
        if (isset($data['code']) && $data['code'] !== $uom->code) {
            $existing = $this->uomRepository->findByCode($data['code']);
            if ($existing && $existing->id !== $uom->id) {
                throw new \Exception("Unit of Measure with code '{$data['code']}' already exists");
            }
        }

        // Prevent changing category if UoM is in use
        if (isset($data['uom_category']) && $data['uom_category'] !== $uom->uom_category) {
            if ($uom->products()->count() > 0) {
                throw new \Exception('Cannot change category for unit of measure that is used by products');
            }
        }

        // Validate is_base_unit changes
        if (isset($data['is_base_unit']) && $data['is_base_unit'] !== $uom->is_base_unit) {
            if ($data['is_base_unit']) {
                // Changing to base unit
                $existingBaseUnit = $this->uomRepository->getByCategory($uom->uom_category)
                    ->where('is_base_unit', true)
                    ->first();

                if ($existingBaseUnit && $existingBaseUnit->id !== $uom->id) {
                    throw new \Exception(
                        "Category '{$uom->uom_category}' already has a base unit: {$existingBaseUnit->name}"
                    );
                }
            } else {
                // Changing from base unit
                throw new \Exception('Cannot remove base unit status once set');
            }
        }
    }
}
