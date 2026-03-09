<?php

declare(strict_types=1);

namespace App\Infrastructure\Database\Repositories;

use App\Domain\Contracts\OrderRepositoryInterface;
use App\Domain\Entities\Order;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Order Repository Implementation
 */
class OrderRepository implements OrderRepositoryInterface
{
    public function __construct(
        protected readonly Order $model
    ) {}

    public function findById(int|string $id, array $relations = []): ?Order
    {
        return $this->model->with($relations)->find($id);
    }

    public function findByOrderNumber(string $orderNumber): ?Order
    {
        return $this->model->where('order_number', $orderNumber)->first();
    }

    public function findByTenant(int|string $tenantId, array $filters = []): Collection|LengthAwarePaginator
    {
        $query = $this->model
            ->where('tenant_id', $tenantId)
            ->when(
                isset($filters['status']),
                fn ($q) => $q->where('status', $filters['status'])
            )
            ->when(
                isset($filters['user_id']),
                fn ($q) => $q->where('user_id', $filters['user_id'])
            )
            ->when(
                isset($filters['search']),
                fn ($q) => $q->where('order_number', 'like', "%{$filters['search']}%")
            )
            ->orderBy(
                $filters['sort_by'] ?? 'created_at',
                $filters['sort_dir'] ?? 'desc'
            );

        if (isset($filters['per_page'])) {
            return $query->paginate(
                (int) $filters['per_page'],
                ['*'],
                'page',
                (int) ($filters['page'] ?? 1)
            );
        }

        return $query->get();
    }

    public function findBySagaId(string $sagaId): ?Order
    {
        return $this->model->where('saga_id', $sagaId)->first();
    }

    public function create(array $data): Order
    {
        return DB::transaction(fn () => $this->model->create($data));
    }

    public function update(int|string $id, array $data): Order
    {
        return DB::transaction(function () use ($id, $data) {
            $order = $this->model->findOrFail($id);
            $order->update($data);
            return $order->fresh(['items']);
        });
    }

    public function updateStatus(int|string $id, string $status): Order
    {
        return $this->update($id, ['status' => $status]);
    }

    public function delete(int|string $id): bool
    {
        return DB::transaction(function () use ($id) {
            $order = $this->model->find($id);
            return $order ? (bool) $order->delete() : false;
        });
    }
}
