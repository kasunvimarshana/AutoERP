<?php

declare(strict_types=1);

namespace Modules\Auth\Services;

use Modules\Core\Results\Result;

final class ValidateTokenService
{
    public function __construct(private readonly AuthWorkflowService $workflow) {}

    public function validateToken(string $accessToken, ?int $tenantId = null): Result
    {
        return $this->workflow->validateToken($accessToken, $tenantId);
    }
}
