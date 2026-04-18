<?php

declare(strict_types=1);

namespace Modules\Auth\Application\UseCases;

use Modules\Auth\Application\Contracts\LoginServiceInterface;
use Modules\Auth\Application\Contracts\RegisterUserServiceInterface;
use Modules\Auth\Domain\Entities\AccessToken;

class RegisterUser
{
    public function __construct(
        private readonly RegisterUserServiceInterface $registerService,
        private readonly LoginServiceInterface $loginService,
    ) {}

    /**
     * Register a new user and immediately issue an access token.
        *
        * @param  array{
        *     tenant_id: int,
        *     email: string,
        *     first_name: string,
        *     last_name: string,
        *     password: string,
        *     phone?: string|null
        * }  $data
     */
    public function execute(array $data): AccessToken
    {
        $this->registerService->register($data);

        return $this->loginService->login($data['email'], $data['password']);
    }
}
