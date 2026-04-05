<?php

declare(strict_types=1);

namespace Modules\Accounting\Application\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Accounting\Domain\Entities\Account;

interface AccountServiceInterface
{
    public function findById(int $id): Account;
    public function findByTenant(int $tenantId, int $perPage = 15, int $page = 1): LengthAwarePaginator;
    public function create(array $data): Account;
    public function update(int $id, array $data): Account;
    public function delete(int $id): bool;
}
