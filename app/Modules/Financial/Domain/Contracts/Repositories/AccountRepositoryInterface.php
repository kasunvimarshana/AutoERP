<?php

declare(strict_types=1);

namespace Modules\Financial\Domain\Contracts\Repositories;

use Illuminate\Support\Collection;
use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;

interface AccountRepositoryInterface extends RepositoryInterface
{
    /**
     * Find an account by its code within the current tenant scope.
     */
    public function findByCode(string $code): mixed;

    /**
     * Get all accounts of a given type.
     */
    public function findByType(string $type): Collection;
}
