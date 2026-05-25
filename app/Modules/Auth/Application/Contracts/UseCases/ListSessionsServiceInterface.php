<?php

declare(strict_types=1);

namespace Modules\Auth\Application\Contracts\UseCases;

use Modules\Core\Application\Results\Result;

interface ListSessionsServiceInterface
{
    public function listSessions(int $userId, ?int $tenantId = null): Result;
}
