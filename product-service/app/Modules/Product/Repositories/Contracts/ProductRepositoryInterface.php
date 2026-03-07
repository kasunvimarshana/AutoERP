<?php

namespace App\Modules\Product\Repositories\Contracts;

use Illuminate\Pagination\LengthAwarePaginator;

interface ProductRepositoryInterface
{
    public function getAllWithFilters(array $filters): LengthAwarePaginator;
    public function findById(int $id);
    public function create(array $data);
    public function update(int $id, array $data);
    public function delete(int $id): bool;
}
