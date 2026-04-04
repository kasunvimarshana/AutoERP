<?php

namespace Modules\Returns\Domain\RepositoryInterfaces;

use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Returns\Domain\Entities\StockReturn;

interface StockReturnRepositoryInterface
{
    public function findById(int $id): ?StockReturn;

    public function findByReturnNumber(int $tenantId, string $returnNumber): ?StockReturn;

    public function findAll(int $tenantId, array $filters = [], int $perPage = 15): LengthAwarePaginator;

    public function create(array $data): StockReturn;

    public function update(StockReturn $return, array $data): StockReturn;

    public function save(StockReturn $return): StockReturn;
}
