<?php

declare(strict_types=1);

namespace Modules\Account\Domain\RepositoryInterfaces;

use Illuminate\Support\Collection;
use Modules\Account\Domain\Entities\Account;
use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;

interface AccountRepositoryInterface extends RepositoryInterface
{
    public function findByCode(int $tenantId, string $code): ?Account;
    public function findByTenant(int $tenantId): Collection;
    public function findByType(int $tenantId, string $type): Collection;
    public function save(Account $account): Account;
}
