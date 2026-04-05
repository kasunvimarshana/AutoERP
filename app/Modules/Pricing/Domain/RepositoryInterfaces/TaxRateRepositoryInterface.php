<?php
declare(strict_types=1);
namespace Modules\Pricing\Domain\RepositoryInterfaces;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Pricing\Domain\Entities\TaxRate;
interface TaxRateRepositoryInterface {
    public function findById(int $id): ?TaxRate;
    public function findByTenant(int $tenantId, int $perPage = 15, int $page = 1): LengthAwarePaginator;
    public function create(array $data): TaxRate;
    public function update(int $id, array $data): ?TaxRate;
    public function delete(int $id): bool;
}
