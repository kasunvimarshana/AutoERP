<?php
declare(strict_types=1);
namespace Modules\Customer\Application\Contracts;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Customer\Domain\Entities\Customer;
interface CustomerServiceInterface {
    public function findById(int $id): Customer;
    public function findByTenant(int $tenantId, int $perPage = 15, int $page = 1): LengthAwarePaginator;
    public function create(array $data): Customer;
    public function update(int $id, array $data): Customer;
    public function delete(int $id): bool;
}
