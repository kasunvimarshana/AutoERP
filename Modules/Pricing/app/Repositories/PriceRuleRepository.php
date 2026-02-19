<?php

declare(strict_types=1);

namespace Modules\Pricing\Repositories;

use App\Core\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Modules\Pricing\Models\PriceRule;

/**
 * PriceRule Repository
 *
 * Handles data access for PriceRule model
 */
class PriceRuleRepository extends BaseRepository
{
    /**
     * Make model instance
     */
    protected function makeModel(): Model
    {
        return new PriceRule;
    }

    /**
     * Get active rules
     */
    public function getActive(): Collection
    {
        return $this->model->newQuery()->active()->get();
    }

    /**
     * Get active rules ordered by priority
     */
    public function getActiveRulesOrderedByPriority(): Collection
    {
        return $this->model->newQuery()
            ->active()
            ->validAt(now())
            ->orderedByPriority()
            ->get();
    }

    /**
     * Find rules valid at specific date
     */
    public function findValidAt(\Illuminate\Support\Carbon $date): Collection
    {
        return $this->model->newQuery()
            ->active()
            ->validAt($date)
            ->orderedByPriority()
            ->get();
    }

    /**
     * Search rules
     */
    public function search(string $query): Collection
    {
        return $this->model->newQuery()
            ->where('name', 'like', "%{$query}%")
            ->orWhere('description', 'like', "%{$query}%")
            ->get();
    }
}
