<?php

declare(strict_types=1);

namespace Modules\Auth\Application\UseCases;

use Modules\Auth\Application\Contracts\ForgotPasswordServiceInterface;

class ForgotPassword
{
    public function __construct(
        private readonly ForgotPasswordServiceInterface $forgotPasswordService,
    ) {}

    public function execute(string $email): bool
    {
        return $this->forgotPasswordService->sendResetLink($email);
    }
}
