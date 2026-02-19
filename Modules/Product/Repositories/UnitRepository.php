<?php

declare(strict_types=1);

namespace Modules\Product\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Core\Repositories\BaseRepository;
use Modules\Product\Models\Unit;

/**
 * Unit Repository
 *
 * Handles data access operations for Unit model with specialized
 * methods for unit type filtering and symbol lookups
 */
class UnitRepository extends BaseRepository
{
    /**
     * Make a new Unit model instance.
     */
    protected function makeModel(): Model
    {
        return new Unit;
    }

    /**
     * Find units by type.
     */
    public function findByType(string $type): Collection
    {
        return $this->model->where('type', $type)->get();
    }

    /**
     * Find unit by symbol.
     */
    public function findBySymbol(string $symbol): ?Model
    {
        return $this->model->where('symbol', $symbol)->first();
    }

    /**
     * Find unit by name.
     */
    public function findByName(string $name): ?Model
    {
        return $this->model->where('name', $name)->first();
    }

    /**
     * Search units.
     */
    public function searchUnits(
        ?string $searchTerm = null,
        ?string $type = null,
        int $perPage = 15
    ): LengthAwarePaginator {
        $query = $this->model->query();

        if ($searchTerm) {
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', "%{$searchTerm}%")
                    ->orWhere('symbol', 'like', "%{$searchTerm}%");
            });
        }

        if ($type) {
            $query->where('type', $type);
        }

        return $query->paginate($perPage);
    }

    /**
     * Get units grouped by type.
     */
    public function getGroupedByType(): array
    {
        $units = $this->model->all();
        $grouped = [];

        foreach ($units as $unit) {
            $type = $unit->type ?: 'other';
            if (! isset($grouped[$type])) {
                $grouped[$type] = [];
            }
            $grouped[$type][] = $unit;
        }

        return $grouped;
    }

    /**
     * Get units used as buying units.
     */
    public function getUsedAsBuyingUnits(): Collection
    {
        return $this->model->has('productsAsBuyingUnit')->get();
    }

    /**
     * Get units used as selling units.
     */
    public function getUsedAsSellingUnits(): Collection
    {
        return $this->model->has('productsAsSellingUnit')->get();
    }

    /**
     * Get units with usage counts.
     */
    public function getWithUsageCounts(): Collection
    {
        return $this->model->withCount([
            'productsAsBuyingUnit',
            'productsAsSellingUnit',
        ])->get();
    }

    /**
     * Check if unit is in use.
     */
    public function isInUse(string $unitId): bool
    {
        $unit = $this->find($unitId);

        if (! $unit) {
            return false;
        }

        return $unit->productsAsBuyingUnit()->exists()
            || $unit->productsAsSellingUnit()->exists();
    }

    /**
     * Get available unit types.
     */
    public function getAvailableTypes(): array
    {
        return $this->model->distinct()
            ->pluck('type')
            ->filter()
            ->values()
            ->toArray();
    }

    /**
     * Find units by metadata field.
     */
    public function findByMetadata(string $key, mixed $value): Collection
    {
        return $this->model->where("metadata->{$key}", $value)->get();
    }

    /**
     * Get all units with relationships.
     */
    public function getAllWithRelations(): Collection
    {
        return $this->model->with([
            'productsAsBuyingUnit',
            'productsAsSellingUnit',
        ])->get();
    }
}
