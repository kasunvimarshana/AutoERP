<?php

namespace App\Modules\Order\Services\Interfaces;

use App\Modules\Order\DTOs\CreateOrderDTO;
use App\Modules\Order\Models\Order;
use Illuminate\Pagination\LengthAwarePaginator;

interface OrderServiceInterface
{
    public function listOrders(array $filters, int $perPage = 15): LengthAwarePaginator;
    public function getOrder(int $id): Order;
    public function createOrder(CreateOrderDTO $dto): Order;
    public function updateOrder(int $id, array $data): Order;
    public function deleteOrder(int $id): void;
    public function updateStatus(int $id, string $status): Order;
    public function cancelOrder(int $id): Order;
}
