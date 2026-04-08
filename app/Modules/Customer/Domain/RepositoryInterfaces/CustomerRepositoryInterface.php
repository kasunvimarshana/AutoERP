<?php

declare(strict_types=1);

namespace Modules\Customer\Domain\RepositoryInterfaces;

use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;

interface CustomerRepositoryInterface extends RepositoryInterface
{
    public function findByCode(string $code, int $tenantId): mixed;

    public function findByEmail(string $email, int $tenantId): mixed;
}
