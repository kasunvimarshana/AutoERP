<?php

declare(strict_types=1);

namespace Modules\Accounting\Infrastructure\Repositories;

use Modules\Accounting\Domain\Contracts\AccountRepositoryContract;
use Modules\Accounting\Domain\Entities\ChartOfAccount;
use Modules\Core\Infrastructure\Repositories\AbstractRepository;

/**
 * Account repository implementation.
 *
 * Extends the tenant-aware AbstractRepository.
 * All queries are automatically scoped to the current tenant.
 */
class AccountRepository extends AbstractRepository implements AccountRepositoryContract
{
    public function __construct()
    {
        $this->modelClass = ChartOfAccount::class;
    }
}
