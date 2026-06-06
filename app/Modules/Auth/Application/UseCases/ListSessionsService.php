<?php

declare(strict_types=1);

namespace Modules\Auth\Application\UseCases;

use Modules\Core\Application\Results\Result;

final class ListSessionsService
{
    public function __construct(private readonly AuthWorkflowService $workflow) {}

    public function listSessions(int $userId, ?int $tenantId = null): Result
    {
        return $this->workflow->listSessions($userId, $tenantId);
    }
}
