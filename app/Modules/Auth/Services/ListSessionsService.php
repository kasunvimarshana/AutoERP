<?php

declare(strict_types=1);

namespace Modules\Auth\Services;

use Modules\Core\Results\Result;

final class ListSessionsService
{
    public function __construct(private readonly AuthWorkflowService $workflow) {}

    public function listSessions(int $userId, ?int $tenantId = null): Result
    {
        return $this->workflow->listSessions($userId, $tenantId);
    }
}
