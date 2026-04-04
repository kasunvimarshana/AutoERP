<?php

namespace Modules\Returns\Domain\RepositoryInterfaces;

use Modules\Returns\Domain\Entities\StockReturnLine;

interface StockReturnLineRepositoryInterface
{
    public function findById(int $id): ?StockReturnLine;

    /** @return StockReturnLine[] */
    public function findByStockReturn(int $stockReturnId): array;

    public function create(array $data): StockReturnLine;

    public function update(StockReturnLine $line, array $data): StockReturnLine;

    public function save(StockReturnLine $line): StockReturnLine;
}
