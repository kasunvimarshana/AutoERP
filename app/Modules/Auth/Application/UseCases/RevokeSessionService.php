<?php

declare(strict_types=1);

namespace Modules\Auth\Application\UseCases;

use Modules\Core\Application\Results\Result;

final class RevokeSessionService
{
    public function __construct(private readonly AuthWorkflowService $workflow) {}

    public function revokeSession(int|string $sessionId, ?int $tenantId = null): Result
    {
        return $this->workflow->revokeSession($sessionId, $tenantId);
    }
}
