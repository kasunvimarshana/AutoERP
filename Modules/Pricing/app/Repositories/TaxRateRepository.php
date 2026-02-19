<?php

declare(strict_types=1);

namespace Modules\Pricing\Repositories;

use App\Core\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Modules\Pricing\Models\TaxRate;

/**
 * TaxRate Repository
 *
 * Handles data access for TaxRate model
 */
class TaxRateRepository extends BaseRepository
{
    /**
     * Make model instance
     */
    protected function makeModel(): Model
    {
        return new TaxRate;
    }

    /**
     * Find by code
     */
    public function findByCode(string $code): ?TaxRate
    {
        return $this->model->newQuery()->where('code', $code)->first();
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
     * Get active tax rates
     */
    public function getActive(): Collection
    {
        return $this->model->newQuery()->active()->get();
    }

    /**
     * Get effective tax rates
     */
    public function getEffective(): Collection
    {
        return $this->model->newQuery()
            ->active()
            ->effectiveAt(now())
            ->orderedByPriority()
            ->get();
    }

    /**
     * Find tax rate for jurisdiction
     */
    public function findForJurisdiction(string $jurisdiction): ?TaxRate
    {
        return $this->model->newQuery()
            ->active()
            ->effectiveAt(now())
            ->forJurisdiction($jurisdiction)
            ->orderedByPriority()
            ->first();
    }

    /**
     * Find tax rate for product category
     */
    public function findForProductCategory(string $category): ?TaxRate
    {
        return $this->model->newQuery()
            ->active()
            ->effectiveAt(now())
            ->forProductCategory($category)
            ->orderedByPriority()
            ->first();
    }

    /**
     * Find tax rate for jurisdiction and category
     */
    public function findForJurisdictionAndCategory(string $jurisdiction, string $category): ?TaxRate
    {
        return $this->model->newQuery()
            ->active()
            ->effectiveAt(now())
            ->forJurisdiction($jurisdiction)
            ->forProductCategory($category)
            ->orderedByPriority()
            ->first();
    }

    /**
     * Search tax rates
     */
    public function search(string $query): Collection
    {
        return $this->model->newQuery()
            ->where('name', 'like', "%{$query}%")
            ->orWhere('code', 'like', "%{$query}%")
            ->orWhere('jurisdiction', 'like', "%{$query}%")
            ->get();
    }
}
