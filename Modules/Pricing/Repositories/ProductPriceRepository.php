<?php

declare(strict_types=1);

namespace Modules\Pricing\Repositories;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Core\Repositories\BaseRepository;
use Modules\Pricing\Enums\PricingStrategy;
use Modules\Pricing\Models\ProductPrice;

/**
 * ProductPrice Repository
 *
 * Handles data access operations for ProductPrice model with
 * specialized methods for location-based and time-based pricing
 */
class ProductPriceRepository extends BaseRepository
{
    /**
     * Make a new ProductPrice model instance.
     */
    protected function makeModel(): Model
    {
        return new ProductPrice;
    }

    /**
     * Get active price for a product at a specific location.
     */
    public function getActivePrice(string $productId, ?string $locationId = null): ?Model
    {
        return $this->model
            ->where('product_id', $productId)
            ->forLocation($locationId)
            ->active()
            ->orderByRaw('location_id IS NULL ASC')
            ->first();
    }

    /**
     * Get all active prices for a product.
     */
    public function getActivePrices(string $productId): Collection
    {
        return $this->model
            ->where('product_id', $productId)
            ->active()
            ->with(['location'])
            ->get();
    }

    /**
     * Get prices by location.
     */
    public function getPricesByLocation(string $locationId, bool $onlyActive = true): Collection
    {
        $query = $this->model->where('location_id', $locationId);

        if ($onlyActive) {
            $query->active();
        }

        return $query->with(['product'])->get();
    }

    /**
     * Get prices by strategy.
     */
    public function getPricesByStrategy(PricingStrategy $strategy, bool $onlyActive = true): Collection
    {
        $query = $this->model->where('strategy', $strategy);

        if ($onlyActive) {
            $query->active();
        }

        return $query->with(['product', 'location'])->get();
    }

    /**
     * Get prices valid at a specific date/time.
     */
    public function getPricesAtDate(Carbon $date, bool $onlyActive = true): Collection
    {
        $query = $this->model
            ->where(function ($q) use ($date) {
                $q->whereNull('valid_from')
                    ->orWhere('valid_from', '<=', $date);
            })
            ->where(function ($q) use ($date) {
                $q->whereNull('valid_until')
                    ->orWhere('valid_until', '>=', $date);
            });

        if ($onlyActive) {
            $query->where('is_active', true);
        }

        return $query->with(['product', 'location'])->get();
    }

    /**
     * Get price history for a product.
     */
    public function getPriceHistory(string $productId, ?string $locationId = null, int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->where('product_id', $productId);

        if ($locationId !== null) {
            $query->where('location_id', $locationId);
        }

        return $query->with(['location'])
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    /**
     * Get upcoming price changes.
     */
    public function getUpcomingPriceChanges(int $daysAhead = 30): Collection
    {
        $today = now();
        $futureDate = now()->addDays($daysAhead);

        return $this->model
            ->where('valid_from', '>', $today)
            ->where('valid_from', '<=', $futureDate)
            ->where('is_active', true)
            ->with(['product', 'location'])
            ->orderBy('valid_from')
            ->get();
    }

    /**
     * Get expiring prices.
     */
    public function getExpiringPrices(int $daysAhead = 30): Collection
    {
        $today = now();
        $futureDate = now()->addDays($daysAhead);

        return $this->model
            ->whereNotNull('valid_until')
            ->where('valid_until', '>=', $today)
            ->where('valid_until', '<=', $futureDate)
            ->where('is_active', true)
            ->with(['product', 'location'])
            ->orderBy('valid_until')
            ->get();
    }

    /**
     * Get expired prices.
     */
    public function getExpiredPrices(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model
            ->whereNotNull('valid_until')
            ->where('valid_until', '<', now())
            ->with(['product', 'location'])
            ->orderByDesc('valid_until')
            ->paginate($perPage);
    }

    /**
     * Get prices by date range.
     */
    public function getPricesByDateRange(string $fromDate, string $toDate, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model
            ->where(function ($query) use ($fromDate, $toDate) {
                $query->whereBetween('valid_from', [$fromDate, $toDate])
                    ->orWhereBetween('valid_until', [$fromDate, $toDate])
                    ->orWhere(function ($q) use ($fromDate, $toDate) {
                        $q->where('valid_from', '<=', $fromDate)
                            ->where(function ($q2) use ($toDate) {
                                $q2->where('valid_until', '>=', $toDate)
                                    ->orWhereNull('valid_until');
                            });
                    });
            })
            ->with(['product', 'location'])
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Get default prices (no location specified).
     */
    public function getDefaultPrices(bool $onlyActive = true): Collection
    {
        $query = $this->model->whereNull('location_id');

        if ($onlyActive) {
            $query->active();
        }

        return $query->with(['product'])->get();
    }

    /**
     * Get location-specific prices.
     */
    public function getLocationSpecificPrices(bool $onlyActive = true): Collection
    {
        $query = $this->model->whereNotNull('location_id');

        if ($onlyActive) {
            $query->active();
        }

        return $query->with(['product', 'location'])->get();
    }

    /**
     * Search prices.
     */
    public function searchPrices(string $searchTerm, bool $onlyActive = true, int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model
            ->whereHas('product', function ($q) use ($searchTerm) {
                $q->where('name', 'like', "%{$searchTerm}%")
                    ->orWhere('code', 'like', "%{$searchTerm}%");
            });

        if ($onlyActive) {
            $query->active();
        }

        return $query->with(['product', 'location'])->paginate($perPage);
    }

    /**
     * Toggle price active status.
     */
    public function toggleActive(string $id): bool
    {
        $price = $this->findOrFail($id);
        $price->is_active = ! $price->is_active;

        return $price->save();
    }

    /**
     * Deactivate price.
     */
    public function deactivate(string $id): bool
    {
        $price = $this->findOrFail($id);
        $price->is_active = false;

        return $price->save();
    }

    /**
     * Activate price.
     */
    public function activate(string $id): bool
    {
        $price = $this->findOrFail($id);
        $price->is_active = true;

        return $price->save();
    }

    /**
     * Bulk deactivate prices.
     */
    public function bulkDeactivate(array $priceIds): int
    {
        return $this->model->whereIn('id', $priceIds)
            ->update(['is_active' => false]);
    }

    /**
     * Bulk activate prices.
     */
    public function bulkActivate(array $priceIds): int
    {
        return $this->model->whereIn('id', $priceIds)
            ->update(['is_active' => true]);
    }

    /**
     * Deactivate all prices for a product.
     */
    public function deactivateProductPrices(string $productId, ?string $exceptPriceId = null): int
    {
        $query = $this->model->where('product_id', $productId);

        if ($exceptPriceId) {
            $query->where('id', '!=', $exceptPriceId);
        }

        return $query->update(['is_active' => false]);
    }

    /**
     * Deactivate all prices for a location.
     */
    public function deactivateLocationPrices(string $locationId): int
    {
        return $this->model->where('location_id', $locationId)
            ->update(['is_active' => false]);
    }

    /**
     * Check if product has active price.
     */
    public function hasActivePrice(string $productId, ?string $locationId = null): bool
    {
        $query = $this->model->where('product_id', $productId)->active();

        if ($locationId !== null) {
            $query->forLocation($locationId);
        }

        return $query->exists();
    }

    /**
     * Get price statistics by strategy.
     */
    public function getStrategyStatistics(): Collection
    {
        return $this->model
            ->selectRaw('strategy, COUNT(*) as count, AVG(CAST(price AS DECIMAL(10,2))) as avg_price')
            ->where('is_active', true)
            ->groupBy('strategy')
            ->get();
    }

    /**
     * Get price statistics by location.
     */
    public function getLocationStatistics(): Collection
    {
        return $this->model
            ->selectRaw('location_id, COUNT(*) as count, AVG(CAST(price AS DECIMAL(10,2))) as avg_price')
            ->where('is_active', true)
            ->groupBy('location_id')
            ->with('location')
            ->get();
    }

    /**
     * Clone price to new location.
     */
    public function cloneToLocation(string $priceId, string $locationId): Model
    {
        $originalPrice = $this->findOrFail($priceId);

        $newPrice = $originalPrice->replicate();
        $newPrice->location_id = $locationId;
        $newPrice->save();

        return $newPrice;
    }

    /**
     * Get filtered prices with advanced options.
     */
    public function getFiltered(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->query();

        if (! empty($filters['product_id'])) {
            $query->where('product_id', $filters['product_id']);
        }

        if (! empty($filters['location_id'])) {
            $query->where('location_id', $filters['location_id']);
        }

        if (! empty($filters['strategy'])) {
            $query->where('strategy', $filters['strategy']);
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        if (! empty($filters['valid_from'])) {
            $query->where('valid_from', '>=', $filters['valid_from']);
        }

        if (! empty($filters['valid_until'])) {
            $query->where('valid_until', '<=', $filters['valid_until']);
        }

        if (! empty($filters['min_price'])) {
            $query->where('price', '>=', $filters['min_price']);
        }

        if (! empty($filters['max_price'])) {
            $query->where('price', '<=', $filters['max_price']);
        }

        $query->with(['product', 'location'])->latest();

        return $query->paginate($perPage);
    }

    /**
     * Get prices for a specific product with pagination.
     */
    public function getByProduct(string $productId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->query()
            ->where('product_id', $productId)
            ->with(['product', 'location'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Find active price for product at location and date.
     */
    public function findActivePriceForCalculation(
        string $productId,
        ?string $locationId = null,
        ?string $date = null
    ): ?Model {
        $query = $this->model->query()
            ->where('product_id', $productId)
            ->forLocation($locationId)
            ->active();

        if ($date) {
            $query->where(function ($q) use ($date) {
                $q->whereNull('valid_from')
                    ->orWhere('valid_from', '<=', $date);
            })
                ->where(function ($q) use ($date) {
                    $q->whereNull('valid_until')
                        ->orWhere('valid_until', '>=', $date);
                });
        }

        return $query->orderByRaw('location_id IS NOT NULL DESC')->first();
    }
}
