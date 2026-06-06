<?php

declare(strict_types=1);

namespace Modules\Auth\Repositories;

use Modules\Core\DTOs\DataRecord;
use Modules\Core\Repositories\Contracts\RepositoryPortInterface;

interface AuthProviderRepositoryInterface extends RepositoryPortInterface
{
    public function findActiveByKey(?int $tenantId, string $providerKey): ?DataRecord;
}
