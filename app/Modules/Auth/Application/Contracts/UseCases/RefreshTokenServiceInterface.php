<?php

declare(strict_types=1);

namespace Modules\Auth\Application\Contracts\UseCases;

use Modules\Auth\Application\DTOs\TokenRefreshData;
use Modules\Core\Application\Results\Result;

interface RefreshTokenServiceInterface
{
    public function refreshToken(TokenRefreshData $data): Result;
}
