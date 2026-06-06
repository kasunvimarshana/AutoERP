<?php

declare(strict_types=1);

namespace Modules\Auth\Services;

use Modules\Auth\DTOs\TokenRefreshData;
use Modules\Core\Results\Result;

final class RefreshTokenService
{
    public function __construct(private readonly AuthWorkflowService $workflow) {}

    public function refreshToken(TokenRefreshData $data): Result
    {
        return $this->workflow->refreshToken($data);
    }
}
