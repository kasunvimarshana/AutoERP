<?php

declare(strict_types=1);

namespace Modules\Finance\Domain\RepositoryInterfaces;

use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;
use Modules\Finance\Domain\Entities\Account;

interface AccountRepositoryInterface extends RepositoryInterface
{
    public function save(Account $account): Account;

    public function findByTenantAndCode(int $tenantId, string $code): ?Account;
}
