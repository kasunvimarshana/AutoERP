<?php

declare(strict_types=1);

namespace Modules\Auth\Application\UseCases;

use Modules\Auth\Application\Contracts\UseCases\RefreshTokenServiceInterface;
use Modules\Auth\Application\DTOs\TokenRefreshData;
use Modules\Core\Application\Results\Result;

final class RefreshTokenService implements RefreshTokenServiceInterface
{
    public function __construct(private readonly AuthWorkflowService $workflow)
    {
    }

    public function refreshToken(TokenRefreshData $data): Result
    {
        return $this->workflow->refreshToken($data);
    }
}
