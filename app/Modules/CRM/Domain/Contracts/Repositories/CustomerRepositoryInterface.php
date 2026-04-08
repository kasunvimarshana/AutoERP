<?php

declare(strict_types=1);

namespace Modules\CRM\Domain\Contracts\Repositories;

use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;

interface CustomerRepositoryInterface extends RepositoryInterface
{
    /**
     * Find a customer by its unique code within the current tenant scope.
     */
    public function findByCode(string $code): mixed;
}
