<?php

declare(strict_types=1);

namespace Modules\Auth\Application\Repositories;

use Modules\Core\Application\DTO\DataRecord;
use Modules\Core\Application\Repositories\Contracts\RepositoryPortInterface;

interface AuthClientRepositoryInterface extends RepositoryPortInterface
{
    public function findByClientKey(?int $tenantId, string $clientKey): ?DataRecord;
}
