<?php

declare(strict_types=1);

namespace Modules\Auth\Repositories;

use Modules\Core\DTOs\DataRecord;
use Modules\Core\Repositories\Contracts\RepositoryPortInterface;

interface AuthAuthorizationCodeRepositoryInterface extends RepositoryPortInterface
{
    public function findActiveByCodeKey(?int $tenantId, string $codeKey): ?DataRecord;

    public function consume(int $codeId, int $expectedRowVersion): bool;
}
