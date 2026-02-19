<?php

declare(strict_types=1);

namespace Modules\Pricing\Repositories;

use App\Core\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Modules\Pricing\Models\DiscountRule;

/**
 * DiscountRule Repository
 *
 * Handles data access for DiscountRule model
 */
class DiscountRuleRepository extends BaseRepository
{
    /**
     * Make model instance
     */
    protected function makeModel(): Model
    {
        return new DiscountRule;
    }

    /**
     * Find by code
     */
    public function findByCode(string $code): ?DiscountRule
    {
        return $this->model->newQuery()->byCode($code)->first();
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
     * Find active rule by code with usage check
     */
    public function findActiveByCode(string $code): ?DiscountRule
    {
        $rule = $this->model->newQuery()
            ->active()
            ->validAt(now())
            ->byCode($code)
            ->first();

        if ($rule && ! $rule->hasUsageRemaining()) {
            return null;
        }

        return $rule;
    }

    /**
     * Search rules
     */
    public function search(string $query): Collection
    {
        return $this->model->newQuery()
            ->where('name', 'like', "%{$query}%")
            ->orWhere('code', 'like', "%{$query}%")
            ->orWhere('description', 'like', "%{$query}%")
            ->get();
    }
}
