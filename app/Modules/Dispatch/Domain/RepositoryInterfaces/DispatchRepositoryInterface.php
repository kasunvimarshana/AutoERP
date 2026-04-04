<?php

namespace Modules\Dispatch\Domain\RepositoryInterfaces;

use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Dispatch\Domain\Entities\Dispatch;

interface DispatchRepositoryInterface
{
    public function findById(int $id): ?Dispatch;
    public function findByDispatchNumber(int $tenantId, string $dispatchNumber): ?Dispatch;
    public function findAll(int $tenantId, array $filters = [], int $perPage = 15): LengthAwarePaginator;
    public function create(array $data): Dispatch;
    public function update(Dispatch $dispatch, array $data): Dispatch;
    public function save(Dispatch $dispatch): Dispatch;
}
