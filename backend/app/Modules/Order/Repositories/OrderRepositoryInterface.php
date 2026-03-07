<?php

namespace App\Modules\Order\Repositories;

use App\Modules\Order\Models\Order;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface OrderRepositoryInterface
{
    public function all(array $filters = []): Collection;

    public function find(int $id): ?Order;

    public function findByOrderNumber(string $orderNumber): ?Order;

    public function create(array $data): Order;

    public function update(int $id, array $data): Order;

    public function delete(int $id): bool;

    public function paginate(
        int $perPage,
        array $filters,
        ?string $search,
        string $sortBy,
        string $sortDir
    ): LengthAwarePaginator;

    public function updateStatus(int $id, string $status): Order;

    public function updateSagaState(int $id, array $sagaState): Order;
}
