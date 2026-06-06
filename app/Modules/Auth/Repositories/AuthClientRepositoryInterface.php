<?php

declare(strict_types=1);

namespace Modules\Auth\Repositories;

use Modules\Core\DTOs\DataRecord;
use Modules\Core\Repositories\Contracts\RepositoryPortInterface;

interface AuthClientRepositoryInterface extends RepositoryPortInterface
{
    public function findByClientKey(?int $tenantId, string $clientKey): ?DataRecord;
}
