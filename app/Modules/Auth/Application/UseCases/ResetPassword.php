<?php

declare(strict_types=1);

namespace Modules\Auth\Application\UseCases;

use Modules\Auth\Application\Contracts\ResetPasswordServiceInterface;

class ResetPassword
{
    public function __construct(
        private readonly ResetPasswordServiceInterface $resetPasswordService,
    ) {}

    /**
     * @param  array{email: string, password: string, password_confirmation: string, token: string}  $data
     */
    public function execute(array $data): bool
    {
        return $this->resetPasswordService->reset($data);
    }
}
