<?php

declare(strict_types=1);

namespace Modules\Auth\Application\UseCases;

use Modules\Auth\Application\Contracts\RefreshTokenServiceInterface;
use Modules\Auth\Domain\Entities\AccessToken;

class RefreshToken
{
    public function __construct(
        private readonly RefreshTokenServiceInterface $refreshTokenService,
    ) {}

    public function execute(int $userId): AccessToken
    {
        return $this->refreshTokenService->refresh($userId);
    }
}
