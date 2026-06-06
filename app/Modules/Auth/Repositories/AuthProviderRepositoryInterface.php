<?php

declare(strict_types=1);

namespace Modules\Auth\Repositories;

use Modules\Core\Contracts\RepositoryPortInterface;
use Modules\Core\DTOs\DataRecord;

interface AuthProviderRepositoryInterface extends RepositoryPortInterface
{
    public function findActiveByKey(?int $tenantId, string $providerKey): ?DataRecord;
}
