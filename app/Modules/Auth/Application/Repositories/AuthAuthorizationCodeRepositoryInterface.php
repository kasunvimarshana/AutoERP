<?php

declare(strict_types=1);

namespace Modules\Auth\Application\Repositories;

use Modules\Core\Application\DTO\DataRecord;
use Modules\Core\Application\Repositories\Contracts\RepositoryPortInterface;

interface AuthAuthorizationCodeRepositoryInterface extends RepositoryPortInterface
{
    public function findActiveByCodeKey(?int $tenantId, string $codeKey): ?DataRecord;

    public function consume(int $codeId, int $expectedRowVersion): bool;
}
