<?php

namespace App\Contracts\Services;

use App\Models\Order;
use Illuminate\Pagination\LengthAwarePaginator;

interface OrderServiceInterface
{
    public function paginate(string $tenantId, array $filters = [], int $perPage = 15): LengthAwarePaginator;

    public function create(array $data): Order;

    public function confirm(string $id): Order;

    public function cancel(string $id): Order;
}
