<?php

declare(strict_types=1);

namespace Modules\Auth\Application\Contracts\UseCases;

use Modules\Core\Application\Results\Result;

interface ValidateTokenServiceInterface
{
    public function validateToken(string $accessToken, ?int $tenantId = null): Result;
}
