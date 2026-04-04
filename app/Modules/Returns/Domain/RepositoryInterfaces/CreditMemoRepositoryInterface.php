<?php

namespace Modules\Returns\Domain\RepositoryInterfaces;

use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Returns\Domain\Entities\CreditMemo;

interface CreditMemoRepositoryInterface
{
    public function findById(int $id): ?CreditMemo;

    public function findByMemoNumber(int $tenantId, string $memoNumber): ?CreditMemo;

    public function findAll(int $tenantId, array $filters = [], int $perPage = 15): LengthAwarePaginator;

    public function create(array $data): CreditMemo;

    public function update(CreditMemo $memo, array $data): CreditMemo;

    public function save(CreditMemo $memo): CreditMemo;
}
