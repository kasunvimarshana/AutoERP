<?php
declare(strict_types=1);
namespace Modules\Dispatch\Domain\RepositoryInterfaces;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Dispatch\Domain\Entities\Dispatch;
interface DispatchRepositoryInterface {
    public function findById(int $id): ?Dispatch;
    public function findByTenant(int $tenantId, int $perPage = 15, int $page = 1): LengthAwarePaginator;
    public function create(array $data, array $lines): Dispatch;
    public function update(int $id, array $data): ?Dispatch;
    public function delete(int $id): bool;
}
