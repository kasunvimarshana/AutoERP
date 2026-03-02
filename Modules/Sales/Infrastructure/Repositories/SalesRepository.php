<?php

declare(strict_types=1);

namespace Modules\Sales\Infrastructure\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Modules\Core\Infrastructure\Repositories\AbstractRepository;
use Modules\Sales\Domain\Contracts\SalesRepositoryContract;
use Modules\Sales\Domain\Entities\Customer;
use Modules\Sales\Domain\Entities\SalesOrder;

/**
 * Sales repository implementation.
 *
 * Extends the tenant-aware AbstractRepository.
 * All queries are automatically scoped to the current tenant via HasTenant global scope.
 */
class SalesRepository extends AbstractRepository implements SalesRepositoryContract
{
    public function __construct()
    {
        $this->modelClass = SalesOrder::class;
    }

    /**
     * {@inheritdoc}
     */
    public function findByOrderNumber(string $orderNumber): ?Model
    {
        return $this->query()->where('order_number', $orderNumber)->first();
    }

    /**
     * {@inheritdoc}
     */
    public function findByCustomer(int $customerId): Collection
    {
        return $this->query()->where('customer_id', $customerId)->get();
    }

    /**
     * {@inheritdoc}
     *
     * Customer has HasTenant global scope; query()->get() ensures it is applied.
     */
    public function allCustomers(): Collection
    {
        return Customer::query()->get();
    }
}
