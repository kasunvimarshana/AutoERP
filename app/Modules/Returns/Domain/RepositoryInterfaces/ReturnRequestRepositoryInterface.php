<?php
declare(strict_types=1);
namespace Modules\Returns\Domain\RepositoryInterfaces;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Returns\Domain\Entities\ReturnRequest;

interface ReturnRequestRepositoryInterface
{
    public function findById(int $id): ?ReturnRequest;
    public function findByTenant(int $tenantId, array $filters = [], int $perPage = 15, int $page = 1): LengthAwarePaginator;
    public function create(array $data, array $lines): ReturnRequest;
    public function update(int $id, array $data): ?ReturnRequest;
    public function updateLine(int $lineId, array $data): bool;
    public function delete(int $id): bool;
}
