<?php

namespace App\Modules\MasterData\Services;

use App\Core\Services\BaseService;
use App\Modules\MasterData\Models\Unit;
use App\Modules\MasterData\Repositories\UnitRepository;
use Illuminate\Support\Facades\DB;

class UnitService extends BaseService
{
    protected UnitRepository $repository;

    public function __construct(UnitRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Create a new unit.
     */
    public function createUnit(array $data): Unit
    {
        return DB::transaction(function () use ($data) {
            return $this->repository->create($data);
        });
    }

    /**
     * Update an existing unit.
     */
    public function updateUnit(int $id, array $data): Unit
    {
        return DB::transaction(function () use ($id, $data) {
            return $this->repository->update($id, $data);
        });
    }

    /**
     * Delete a unit.
     */
    public function deleteUnit(int $id): bool
    {
        return $this->repository->delete($id);
    }

    /**
     * Get units by type.
     */
    public function getUnitsByType(string $type)
    {
        return Unit::active()->ofType($type)->get();
    }

    /**
     * Convert value between units.
     */
    public function convertUnits(float $value, int $fromUnitId, int $toUnitId): float
    {
        $fromUnit = $this->repository->find($fromUnitId);
        $toUnit = $this->repository->find($toUnitId);

        if (!$fromUnit || !$toUnit) {
            throw new \InvalidArgumentException('Invalid unit IDs');
        }

        if ($fromUnit->type !== $toUnit->type) {
            throw new \InvalidArgumentException('Cannot convert between different unit types');
        }

        // Convert to base unit first, then to target unit
        $baseValue = $fromUnit->toBaseUnit($value);
        return $toUnit->fromBaseUnit($baseValue);
    }
}
