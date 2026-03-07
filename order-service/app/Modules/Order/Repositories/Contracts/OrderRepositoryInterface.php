<?php

namespace App\Modules\Order\Repositories\Contracts;

use Illuminate\Pagination\LengthAwarePaginator;

interface OrderRepositoryInterface
{
    public function getAllWithFilters(array $filters): LengthAwarePaginator;
    public function findById(int $id);
    public function create(array $data);
}
