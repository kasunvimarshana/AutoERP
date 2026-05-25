<?php

declare(strict_types=1);

namespace Modules\Auth\Application\Repositories;

use DateTimeInterface;
use Modules\Core\Application\Repositories\Contracts\RepositoryPortInterface;

interface AuthLoginAttemptRepositoryInterface extends RepositoryPortInterface
{
    public function countRecentFailures(
        ?int $tenantId,
        string $loginIdentifier,
        ?string $ipAddress,
        DateTimeInterface $since,
    ): int;

    public function clearRecentFailures(
        ?int $tenantId,
        string $loginIdentifier,
        ?string $ipAddress,
        DateTimeInterface $since,
    ): void;
}
