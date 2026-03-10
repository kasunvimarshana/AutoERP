<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repositories;

use App\Domain\Order\Repositories\OrderRepositoryInterface;
use App\Infrastructure\Persistence\Models\Order;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * OrderRepository
 */
class OrderRepository extends BaseRepository implements OrderRepositoryInterface
{
    public function __construct(Order $model)
    {
        parent::__construct($model);
    }

    public function listForTenant(
        string $tenantId,
        array  $filters  = [],
        int    $perPage  = 15
    ): LengthAwarePaginator {
        $filters['tenant_id'] = $tenantId;

        return $this->paginate(
            $filters,
            $perPage,
            ['*'],
            'page',
            null,
            ['items'],
            ['created_at' => 'desc']
        );
    }
}
