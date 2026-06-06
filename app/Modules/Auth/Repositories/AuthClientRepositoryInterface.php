<?php

declare(strict_types=1);

namespace Modules\Auth\Repositories;

use Modules\Core\Contracts\RepositoryPortInterface;
use Modules\Core\DTOs\DataRecord;

interface AuthClientRepositoryInterface extends RepositoryPortInterface
{
    public function findByClientKey(?int $tenantId, string $clientKey): ?DataRecord;
}
