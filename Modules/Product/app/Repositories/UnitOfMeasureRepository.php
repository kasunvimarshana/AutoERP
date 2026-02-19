<?php

declare(strict_types=1);

namespace Modules\Product\Repositories;

use App\Core\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Modules\Product\Models\UnitOfMeasure;

/**
 * Unit of Measure Repository
 *
 * Handles data access for UnitOfMeasure model
 */
class UnitOfMeasureRepository extends BaseRepository
{
    /**
     * {@inheritDoc}
     */
    protected function makeModel(): Model
    {
        return new UnitOfMeasure;
    }

    /**
     * Find UoM by code
     */
    public function findByCode(string $code): ?UnitOfMeasure
    {
        /** @var UnitOfMeasure|null */
        return $this->findOneBy(['code' => $code]);
    }

    /**
     * Check if code exists
     */
    public function codeExists(string $code, ?int $excludeId = null): bool
    {
        $query = $this->model->newQuery()->where('code', $code);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    /**
     * Get active units
     */
    public function getActive(): Collection
    {
        return $this->model->newQuery()->where('is_active', true)->get();
    }

    /**
     * Get units by type
     */
    public function getByType(string $type): Collection
    {
        return $this->model->newQuery()->where('type', $type)->get();
    }

    /**
     * Get base units
     */
    public function getBaseUnits(): Collection
    {
        return $this->model->newQuery()->where('is_base_unit', true)->get();
    }

    /**
     * Get unit with conversions
     */
    public function findWithConversions(int $id): ?UnitOfMeasure
    {
        /** @var UnitOfMeasure|null */
        return $this->model->newQuery()
            ->with(['conversionsFrom', 'conversionsTo'])
            ->find($id);
    }
}
