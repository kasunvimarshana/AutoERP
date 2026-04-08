<?php

declare(strict_types=1);

namespace Modules\Auth\Application\UseCases;

use Modules\Auth\Application\Contracts\LoginServiceInterface;
use Modules\Auth\Domain\Entities\AccessToken;

class LoginUser
{
    public function __construct(
        private readonly LoginServiceInterface $loginService,
    ) {}

    public function execute(string $email, string $password): AccessToken
    {
        return $this->loginService->login($email, $password);
    }
}
