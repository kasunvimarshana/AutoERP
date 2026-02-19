<?php

declare(strict_types=1);

namespace Modules\Pricing\Repositories;

use App\Core\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Modules\Pricing\Models\PriceList;

/**
 * PriceList Repository
 *
 * Handles data access for PriceList model
 */
class PriceListRepository extends BaseRepository
{
    /**
     * Make model instance
     */
    protected function makeModel(): Model
    {
        return new PriceList;
    }

    /**
     * Find by code
     */
    public function findByCode(string $code): ?PriceList
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
     * Get active price lists
     */
    public function getActive(): Collection
    {
        return $this->model->newQuery()->active()->get();
    }

    /**
     * Get default price list
     */
    public function getDefault(): ?PriceList
    {
        return $this->model->newQuery()->active()->default()->first();
    }

    /**
     * Find active price list for customer
     */
    public function findActiveForCustomer(int $customerId): ?PriceList
    {
        return $this->model->newQuery()
            ->active()
            ->validAt(now())
            ->forCustomer($customerId)
            ->orderBy('priority', 'desc')
            ->first();
    }

    /**
     * Find active price list for location
     */
    public function findActiveForLocation(string $locationCode): ?PriceList
    {
        return $this->model->newQuery()
            ->active()
            ->validAt(now())
            ->forLocation($locationCode)
            ->orderBy('priority', 'desc')
            ->first();
    }

    /**
     * Find active price list for customer group
     */
    public function findActiveForCustomerGroup(string $group): ?PriceList
    {
        return $this->model->newQuery()
            ->active()
            ->validAt(now())
            ->forCustomerGroup($group)
            ->orderBy('priority', 'desc')
            ->first();
    }

    /**
     * Get price lists with items
     */
    public function getAllWithItems(): Collection
    {
        return $this->model->newQuery()->with('items.product')->get();
    }

    /**
     * Find price list with items
     */
    public function findWithItems(int $id): ?PriceList
    {
        return $this->model->newQuery()->with('items.product')->find($id);
    }

    /**
     * Search price lists
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
