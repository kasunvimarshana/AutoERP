<?php

declare(strict_types=1);

namespace Modules\Financial\Domain\Contracts\Repositories;

use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;

interface FiscalYearRepositoryInterface extends RepositoryInterface
{
    /**
     * Find the open fiscal year for the current tenant.
     */
    public function findOpen(): mixed;
}
