<?php

namespace App\Modules\Order\Repositories\Interfaces;

use App\Modules\Order\Models\Order;
use Illuminate\Pagination\LengthAwarePaginator;

interface OrderRepositoryInterface
{
    public function paginate(array $filters, int $perPage = 15): LengthAwarePaginator;
    public function findById(int $id): ?Order;
    public function findByOrderNumber(string $orderNumber): ?Order;
    public function create(array $data): Order;
    public function update(int $id, array $data): ?Order;
    public function delete(int $id): bool;
    public function updateStatus(int $id, string $status): ?Order;
}
