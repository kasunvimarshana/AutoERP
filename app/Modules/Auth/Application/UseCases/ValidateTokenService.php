<?php

declare(strict_types=1);

namespace Modules\Auth\Application\UseCases;

use Modules\Auth\Application\Contracts\UseCases\ValidateTokenServiceInterface;
use Modules\Core\Application\Results\Result;

final class ValidateTokenService implements ValidateTokenServiceInterface
{
    public function __construct(private readonly AuthWorkflowService $workflow)
    {
    }

    public function validateToken(string $accessToken, ?int $tenantId = null): Result
    {
        return $this->workflow->validateToken($accessToken, $tenantId);
    }
}
