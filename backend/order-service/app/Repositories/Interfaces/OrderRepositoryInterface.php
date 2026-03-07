<?php

namespace App\Repositories\Interfaces;

use App\Models\Order;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface OrderRepositoryInterface
{
    public function findById(int $id): ?Order;

    public function create(array $data): Order;

    public function update(Order $order, array $data): Order;

    public function delete(Order $order): bool;

    public function paginate(array $filters = [], ?int $perPage = null): LengthAwarePaginator|Collection;
}
